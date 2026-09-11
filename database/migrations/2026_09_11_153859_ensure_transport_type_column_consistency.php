<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * This migration ensures the database schema consistency for the transport_type column.
 * It handles various scenarios to ensure the column exists with the correct name,
 * regardless of the current state of the database.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * - Renames 'tent_type' to 'transport_type' if it exists and 'transport_type' doesn't
     * - Creates 'transport_type' if neither column exists
     * - Adds index for performance optimization
     */
    public function up(): void
    {
        try {
            Schema::table('user_steps', function (Blueprint $table) {
                // First check if tent_type exists but transport_type doesn't
                $hasTentType = Schema::hasColumn('user_steps', 'tent_type');
                $hasTransportType = Schema::hasColumn('user_steps', 'transport_type');

                Log::info('Checking columns in user_steps table', [
                    'has_tent_type' => $hasTentType,
                    'has_transport_type' => $hasTransportType
                ]);

                if ($hasTentType && !$hasTransportType) {
                    // Rename tent_type to transport_type
                    Log::info('Renaming tent_type to transport_type');
                    $table->renameColumn('tent_type', 'transport_type');
                } else if (!$hasTentType && !$hasTransportType) {
                    // Neither column exists, create transport_type
                    Log::info('Creating transport_type column');
                    $table->string('transport_type')->nullable();
                } else if ($hasTentType && $hasTransportType) {
                    // Both exist (unusual case) - keep transport_type, drop tent_type
                    Log::warning('Both tent_type and transport_type exist, dropping tent_type');

                    // Copy data from tent_type to transport_type if transport_type is null
                    DB::statement("
                        UPDATE user_steps
                        SET transport_type = tent_type
                        WHERE transport_type IS NULL AND tent_type IS NOT NULL
                    ");

                    $table->dropColumn('tent_type');
                }

                // Add index on transport_type if it doesn't exist already
                if (!$this->hasIndex('user_steps', 'user_steps_transport_type_index')) {
                    Log::info('Adding index on transport_type column');
                    $table->index('transport_type');
                }

                // Add index on chat_id if it doesn't exist already
                if (!$this->hasIndex('user_steps', 'user_steps_chat_id_index')) {
                    Log::info('Adding index on chat_id column');
                    $table->index('chat_id');
                }
            });

            Log::info('Successfully completed transport_type column consistency migration');
        } catch (\Exception $e) {
            Log::error('Error in transport_type consistency migration', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    /**
     * Reverse the migrations.
     * - Restores the old column structure if needed
     * - Removes added indexes
     */
    public function down(): void
    {
        try {
            Schema::table('user_steps', function (Blueprint $table) {
                // Remove indexes if they exist
                if ($this->hasIndex('user_steps', 'user_steps_transport_type_index')) {
                    $table->dropIndex('user_steps_transport_type_index');
                }

                if ($this->hasIndex('user_steps', 'user_steps_chat_id_index')) {
                    $table->dropIndex('user_steps_chat_id_index');
                }

                // Rename transport_type back to tent_type
                if (Schema::hasColumn('user_steps', 'transport_type')) {
                    $table->renameColumn('transport_type', 'tent_type');
                }
            });

            Log::info('Successfully reversed transport_type column consistency migration');
        } catch (\Exception $e) {
            Log::error('Error in reversing transport_type consistency migration', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table The table name
     * @param string $index The index name
     * @return bool Whether the index exists
     */
    private function hasIndex($table, $index): bool
    {
        try {
            $conn = Schema::getConnection();
            $dbSchemaManager = $conn->getDoctrineSchemaManager();
            $doctrineTable = $dbSchemaManager->listTableDetails($table);

            return $doctrineTable->hasIndex($index);
        } catch (\Exception $e) {
            Log::warning('Error checking for index existence', [
                'table' => $table,
                'index' => $index,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
};
