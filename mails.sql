-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: express-pochta
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.22.04.4

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `mails`
--

DROP TABLE IF EXISTS `mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint DEFAULT NULL,
  `step` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_price` decimal(10,2) DEFAULT NULL,
  `currier_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` json DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `oferta_checked` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mails`
--

LOCK TABLES `mails` WRITE;
/*!40000 ALTER TABLE `mails` DISABLE KEYS */;
INSERT INTO `mails` VALUES (1,6757738816,'lang_select',NULL,'asdsad','asd','+998946607678','asfd','sdf','sdf','asfd','✅ Ekonom (to 14 kun)','1',185641.00,'+998991655101','[]','pending',1,'2025-06-28 03:25:35','2025-07-03 01:48:54'),(2,1496582085,'completed','uz','Sardor','Toshkent','+998935009499','Ali','Moscov','Russia','1234','🕐 Super-ekspress (24–72 soat)','12',1200000.00,'+998991655101','[\"AgACAgIAAxkBAAOSaF_y5OZ46Zv96BUwmbEed2Ek1WUAAsz1MRt01wFLlJnTIAzqyx4BAAMCAAN4AAM2BA\"]','pending',0,'2025-06-28 03:25:54','2025-06-28 08:49:25'),(3,1795289463,'completed','ru','Давлетова Юлдуз','Адрес: Казань, Улица Рихарда Зорге 83, подъезд 4, квартира 129','+79370071230','Икрамова Лаурахон','Ташкент','Узбекистан','12345','🚚 Стандарт (5–10 дней)','7',880000.00,'+998933999058','[\"AgACAgIAAxkBAAIHGGiGW9BvPIqwLFQcOYPo-FkZCb0WAAKw8zEbN_s4SAhdKNBnoc5wAQADAgADeQADNgQ\"]','pending',1,'2025-06-28 08:32:57','2025-07-27 12:03:13'),(4,1893978080,'completed','uz','Нурмухаммад Султоналиев','Тошкент шахар','+998958350999','Даурен Зулпаров','Алмата','Қозоғистон','050014','🚚 Standart (5–10 kun)','10',440000.00,'+998933999058','[\"AgACAgIAAxkBAAIHtGiTNw--ayGa5vzl7WlbBkkrRtpWAAIhAzIblCehSAY0ad25qUJZAQADAgADdwADNgQ\"]','pending',1,'2025-06-28 08:33:00','2025-08-06 06:05:52'),(5,8099579069,'completed','uz','Ulugbek Agzamovich','Toshkent shaxar','+998946602505','Absarova Moxinur','Turkiya','Istanbul','999999','🚚 Standart (5–10 kun)','4',760000.00,'+998933999058','[\"AgACAgIAAxkBAAIBEGhf86Nfom92tDUjrczkRu80XToGAAJp9jEbSl8AAUtgdKkvMOJ6_AEAAwIAA3kAAzYE\"]','rejected',1,'2025-06-28 08:33:02','2025-06-28 08:58:07'),(6,6302867774,'completed','uz','Сафаров Улугбек Азамович','Тошкент шахар Себзор кучаси 4 уй','+998946602505','Полвонова Малика','Проспект Москва','Россия','140100','🚚 Standart (5–10 kun)','7',670000.00,'+998933999058','[\"AgACAgIAAxkBAAIBI2hf87vvp8LcZQPt6uNSSmXUajfqAAJU9zEb_WAAAUsW0fVa9VulHwEAAwIAA3kAAzYE\"]','rejected',1,'2025-06-28 08:33:28','2025-06-28 08:57:25'),(7,5329655202,'completed','uz','Мамаджанов Рустам','Тошкент','+998977205505','Global Blue','Slovakia','Slovakia','82109','🚚 Standart (5–10 kun)','10',670000.00,'+998933999058','[\"AgACAgIAAxkBAAIH6miUR6xmLJ9QQUzjxEOxIbtjoxssAAKO_DEbOnOpSLYviA5rohkeAQADAgADdwADNgQ\"]','pending',1,'2025-06-28 08:33:37','2025-08-07 01:29:00'),(8,7876703350,'recipient_name','uz','Nurullayev Aloviddin','Samarqand shaxri','+998902855001','Contact: Karina Matvyeyeva','Wybrzeże Kościuszkowskie 31/33\n00-379 Warszaw, Poland \n9 floor \nCompany: Sava Translogic','Polsha','1200','🚚 Standart (5–10 kun)','10',640.00,'+998933999053','[]','pending',1,'2025-06-28 08:35:52','2025-08-18 06:32:55'),(9,1897452876,'completed','ru','Юлия','Ташкент','+998909216370','Нафиса','Казахстан','Алматы','050008','🚚 Стандарт (5–10 дней)','3',700000.00,'+998933999058','[\"AgACAgIAAxkBAAIIxGikB9xi-mH9jy-RgInyVIQozvlLAAID_DEbmTbZSGbmZbNhXbnzAQADAgADeQADNgQ\"]','pending',0,'2025-06-30 00:13:03','2025-08-19 00:13:00'),(10,1694189721,'completed','uz','Akmal Qodirov','100025\nг. Ташкент, Мирабадский район, ул. Мироншоха, 7 проезд, D26 (ЖК Parkwood)','+998953443151','Радченко Кристина','Адрес: г.Бишкек, пр. Ч.Айтматова 4 кв.89','Qirgʻiziston','720017','🚚 Standart (5–10 kun)','7',440000.00,'+998933999058','[\"AgACAgIAAxkBAAIFC2h16LUJemUMQ8ArLYy5klA9CeMyAALZ_TEbolE4S_Vb8xX_eoPjAQADAgADeQADNgQ\"]','accepted',1,'2025-07-02 04:03:44','2025-07-15 00:38:09'),(11,1978784305,'lang_select',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]','pending',0,'2025-07-03 01:48:54','2025-07-03 01:48:54');
/*!40000 ALTER TABLE `mails` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-11 17:18:23
