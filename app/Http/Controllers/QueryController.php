<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Query;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

#[OA\Tag(
    name: 'Queries',
    description: 'Query API endpoints'
)]
class QueryController extends Controller
{
    #[OA\Get(
        path: '/api/queries',
        summary: 'Get all finished queries',
        tags: ['Queries'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'custom_id', type: 'string', example: 'abc123'),
                                    new OA\Property(property: 'count', type: 'integer', example: 10),
                                    new OA\Property(property: 'telegram_group_count', type: 'integer', example: 5),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No data found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'No data found'),
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        $queries = Query::query()
            ->where('status', 17)
            ->where('is_finished', true)
            ->select('custom_id', 'count')
            ->paginate(50); // 🔥 MUHIM

        $groupCount = Group::count();

        if ($queries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No data found',
            ]);
        }

        $queries->getCollection()->transform(function ($query) use ($groupCount) {
            $query->telegram_group_count = $groupCount;
            $query->status = 'Отмена';
            return $query;
        });

        return response()->json([
            'success' => true,
            'data' => $queries,
        ]);
    }


    #[OA\Post(
        path: '/api/query/check',
        operationId: 'checkTelegram',
        summary: 'Check Telegram by custom_id',
        description: 'Search Telegram messages globally using custom_id and return count',
        tags: ['Queries'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['custom_id'],
                properties: [
                    new OA\Property(property: 'custom_id', type: 'string', example: 'ABC123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'custom_id', type: 'string', example: 'ABC123'),
                        new OA\Property(property: 'count', type: 'integer', example: 25),
                        new OA\Property(property: 'crm_status', type: 'integer', example: 200),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function checkQuery(Request $request)
    {
        $request->validate([
            'custom_id' => 'required|string'
        ]);

        $query = Query::where('custom_id', $request->custom_id)->first();


        if (!$query) {
            return response()->json([
                'error' => 'Not found'
            ], 404);
        }

        // qayta ishlash uchun reset qilamiz
        $query->update([
            'check_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Processing started',
            'id' => $query->id
        ]);
    }
    #[OA\Get(
        path: '/api/query/{customId}/cancelled',
        summary: 'Get single query by custom_id',
        tags: ['Queries'],
        parameters: [
            new OA\Parameter(
                name: 'customId',
                description: 'Custom ID of query',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'EGS00002')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data fetched'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'custom_id', type: 'string', example: 'abc123'),
                                new OA\Property(property: 'count', type: 'integer', example: 10),
                                new OA\Property(property: 'telegram_group_count', type: 'integer', example: 5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Data not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data not found'),
                    ]
                )
            ),
        ]
    )]
    public function cancelled($customId)
    {
        $query = Query::query()
            ->where('custom_id', $customId)
            ->where('status', 17)
            ->first();

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found',
            ]);
        }

        $groupCount = Group::query()->count();
        if (!$groupCount) {
            return response()->json([
                'success' => false,
                'message' => 'No groups found',
            ]);
        }

        $query->telegram_group_count = $groupCount;

        return response()->json([
            'success' => true,
            'message' => 'Data fetched',
            'data' => $query,
        ]);
    }


    #[OA\Get(
        path: '/api/query/{customId}',
        summary: 'Get single query by custom_id',
        tags: ['Queries'],
        parameters: [
            new OA\Parameter(
                name: 'customId',
                description: 'Custom ID of query',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'EGS00002')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data fetched'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'custom_id', type: 'string', example: 'abc123'),
                                new OA\Property(property: 'count', type: 'integer', example: 10),
                                new OA\Property(property: 'telegram_group_count', type: 'integer', example: 5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Data not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Data not found'),
                    ]
                )
            ),
        ]
    )]
    public function show($customId)
    {
        $query = Query::query()
            ->where('custom_id', $customId)
            ->first();

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found',
            ]);
        }

        $groupCount = Group::query()->count();
        if (!$groupCount) {
            return response()->json([
                'success' => false,
                'message' => 'No groups found',
            ]);
        }

        $query->telegram_group_count = $groupCount;

        return response()->json([
            'success' => true,
            'message' => 'Data fetched',
            'data' => $query,
        ]);
    }


    public function test()
    {
        $groupCount = Group::query()->count();

        return response()->json([
            'success' => true,
            'message' => 'Data fetched',
            'data' => $groupCount,
        ]);
    }


    #[OA\Get(
        path: '/api/query/result/{custom_id}',
        operationId: 'getTelegramQueryResult',
        summary: 'Get Telegram check result',
        description: "Returns Telegram search result by custom_id. If the process is not finished yet, status will be 'processing'.",
        tags: ['Queries'],
        parameters: [
            new OA\Parameter(
                name: 'custom_id',
                description: 'Unique identifier used for Telegram search',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'ABC123')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Result retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'custom_id', type: 'string', example: 'ABC123'),
                        new OA\Property(property: 'count', type: 'integer', example: 25),
                        new OA\Property(property: 'status', type: 'string', enum: ['processing', 'done'], example: 'done'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Query not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                    ]
                )
            ),
        ]
    )]
    public function getResult($customId)
    {
        $query = Query::where('custom_id', $customId)->first();

        if (!$query) {
            return response()->json([
                'success' => false
            ], 404);
        }
        $groupCount = Group::query()->count();
        return response()->json([
            'success' => true,
            'custom_id' => $customId,
            'count' => $query->count,
            'telegram_group_count' => $groupCount,
            'status' => $query->is_finished ? 'done' : 'processing'
        ]);
    }
}
