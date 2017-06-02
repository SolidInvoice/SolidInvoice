-- MySQL dump 10.13  Distrib 5.7.18, for osx10.12 (x86_64)
--
-- Host: localhost    Database: csbill
-- ------------------------------------------------------
-- Server version	5.7.18

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acl_classes`
--

DROP TABLE IF EXISTS `acl_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acl_classes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_type` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_69DD750638A36066` (`class_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acl_classes`
--

LOCK TABLES `acl_classes` WRITE;
/*!40000 ALTER TABLE `acl_classes` DISABLE KEYS */;
/*!40000 ALTER TABLE `acl_classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acl_entries`
--

DROP TABLE IF EXISTS `acl_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acl_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` int(10) unsigned NOT NULL,
  `object_identity_id` int(10) unsigned DEFAULT NULL,
  `security_identity_id` int(10) unsigned NOT NULL,
  `field_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ace_order` smallint(5) unsigned NOT NULL,
  `mask` int(11) NOT NULL,
  `granting` tinyint(1) NOT NULL,
  `granting_strategy` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `audit_success` tinyint(1) NOT NULL,
  `audit_failure` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_46C8B806EA000B103D9AB4A64DEF17BCE4289BF4` (`class_id`,`object_identity_id`,`field_name`,`ace_order`),
  KEY `IDX_46C8B806EA000B103D9AB4A6DF9183C9` (`class_id`,`object_identity_id`,`security_identity_id`),
  KEY `IDX_46C8B806EA000B10` (`class_id`),
  KEY `IDX_46C8B8063D9AB4A6` (`object_identity_id`),
  KEY `IDX_46C8B806DF9183C9` (`security_identity_id`),
  CONSTRAINT `FK_46C8B8063D9AB4A6` FOREIGN KEY (`object_identity_id`) REFERENCES `acl_object_identities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_46C8B806DF9183C9` FOREIGN KEY (`security_identity_id`) REFERENCES `acl_security_identities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_46C8B806EA000B10` FOREIGN KEY (`class_id`) REFERENCES `acl_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acl_entries`
--

LOCK TABLES `acl_entries` WRITE;
/*!40000 ALTER TABLE `acl_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `acl_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acl_object_identities`
--

DROP TABLE IF EXISTS `acl_object_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acl_object_identities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_object_identity_id` int(10) unsigned DEFAULT NULL,
  `class_id` int(10) unsigned NOT NULL,
  `object_identifier` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `entries_inheriting` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_9407E5494B12AD6EA000B10` (`object_identifier`,`class_id`),
  KEY `IDX_9407E54977FA751A` (`parent_object_identity_id`),
  CONSTRAINT `FK_9407E54977FA751A` FOREIGN KEY (`parent_object_identity_id`) REFERENCES `acl_object_identities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acl_object_identities`
--

LOCK TABLES `acl_object_identities` WRITE;
/*!40000 ALTER TABLE `acl_object_identities` DISABLE KEYS */;
/*!40000 ALTER TABLE `acl_object_identities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acl_object_identity_ancestors`
--

DROP TABLE IF EXISTS `acl_object_identity_ancestors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acl_object_identity_ancestors` (
  `object_identity_id` int(10) unsigned NOT NULL,
  `ancestor_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`object_identity_id`,`ancestor_id`),
  KEY `IDX_825DE2993D9AB4A6` (`object_identity_id`),
  KEY `IDX_825DE299C671CEA1` (`ancestor_id`),
  CONSTRAINT `FK_825DE2993D9AB4A6` FOREIGN KEY (`object_identity_id`) REFERENCES `acl_object_identities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_825DE299C671CEA1` FOREIGN KEY (`ancestor_id`) REFERENCES `acl_object_identities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acl_object_identity_ancestors`
--

LOCK TABLES `acl_object_identity_ancestors` WRITE;
/*!40000 ALTER TABLE `acl_object_identity_ancestors` DISABLE KEYS */;
/*!40000 ALTER TABLE `acl_object_identity_ancestors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `acl_security_identities`
--

DROP TABLE IF EXISTS `acl_security_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acl_security_identities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `username` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8835EE78772E836AF85E0677` (`identifier`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acl_security_identities`
--

LOCK TABLES `acl_security_identities` WRITE;
/*!40000 ALTER TABLE `acl_security_identities` DISABLE KEYS */;
/*!40000 ALTER TABLE `acl_security_identities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `street1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6FCA751619EB6921` (`client_id`),
  CONSTRAINT `FK_6FCA751619EB6921` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (1,1,'OneAS','TwoAS','Three','Four','1234','ZA','2017-05-28 18:49:38','2017-05-28 19:22:56',NULL),(2,1,'Five','Six','Sevem','Eight','9876','ZA','2017-05-28 18:49:38','2017-05-28 18:49:38',NULL);
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_token_history`
--

DROP TABLE IF EXISTS `api_token_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_token_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token_id` int(11) DEFAULT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `resource` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  `method` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `requestData` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `userAgent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_61D8DC4441DEE7B9` (`token_id`),
  CONSTRAINT `FK_61D8DC4441DEE7B9` FOREIGN KEY (`token_id`) REFERENCES `api_tokens` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_token_history`
--

LOCK TABLES `api_token_history` WRITE;
/*!40000 ALTER TABLE `api_token_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_token_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(125) COLLATE utf8_bin NOT NULL,
  `token` varchar(125) COLLATE utf8_bin NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2CAD560EA76ED395` (`user_id`),
  CONSTRAINT `FK_2CAD560EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_tokens`
--

LOCK TABLES `api_tokens` WRITE;
/*!40000 ALTER TABLE `api_tokens` DISABLE KEYS */;
INSERT INTO `api_tokens` VALUES (1,1,'test','4eb3a49a733cfc12ee342bdcd8d9b7ca1136d386b074c703b1344b48bde6246a','2017-05-28 19:28:29','2017-05-28 19:28:29'),(2,1,'resr','bb414f57fba761a5ec18157d4dc520060b232cd32845d22da24339d40d2da3da','2017-05-29 18:57:12','2017-05-29 18:57:12');
/*!40000 ALTER TABLE `api_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_config`
--

DROP TABLE IF EXISTS `app_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  `setting_value` longtext COLLATE utf8_unicode_ci,
  `description` longtext COLLATE utf8_unicode_ci,
  `section_id` int(11) DEFAULT NULL,
  `field_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `field_options` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_318942FCD823E37A` (`section_id`),
  CONSTRAINT `FK_318942FCD823E37A` FOREIGN KEY (`section_id`) REFERENCES `config_sections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_config`
--

LOCK TABLES `app_config` WRITE;
/*!40000 ALTER TABLE `app_config` DISABLE KEYS */;
INSERT INTO `app_config` VALUES (1,'app_name','CSBill',NULL,2,NULL,'a:0:{}'),(2,'logo','592b23d57e4c8.png',NULL,2,'image_upload','a:0:{}'),(3,'email_subject','New Quotation - #{id}','To include the id of the quote in the subject, add the placeholder {id} where you want the id',3,NULL,'a:0:{}'),(4,'email_subject','New Invoice - #{id}','To include the id of the invoice in the subject, add the placeholder {id} where you want the id',4,NULL,'a:0:{}'),(5,'from_name','CSBill',NULL,5,NULL,'a:0:{}'),(6,'from_address','info@csbill.org',NULL,5,NULL,'a:0:{}'),(7,'format','both','In what format should emails be sent.',5,'radio','a:3:{s:4:\"html\";s:4:\"html\";s:4:\"text\";s:4:\"text\";s:4:\"both\";s:4:\"both\";}'),(8,'auth_token',NULL,NULL,6,NULL,'a:0:{}'),(9,'room_id',NULL,NULL,6,NULL,'a:0:{}'),(10,'server_url','https://api.hipchat.com',NULL,6,NULL,'a:0:{}'),(11,'notify','',NULL,6,'checkbox','a:0:{}'),(12,'message_color','yellow',NULL,6,'select2','a:6:{s:6:\"yellow\";s:6:\"yellow\";s:3:\"red\";s:3:\"red\";s:4:\"gray\";s:4:\"gray\";s:5:\"green\";s:5:\"green\";s:6:\"purple\";s:6:\"purple\";s:6:\"random\";s:6:\"random\";}'),(13,'number',NULL,NULL,8,'text','a:0:{}'),(14,'sid',NULL,NULL,8,'text','a:0:{}'),(15,'token',NULL,NULL,8,'text','a:0:{}'),(16,'bcc_address',NULL,'Send BCC copy of invoice to this address',4,'email','a:0:{}'),(17,'bcc_address',NULL,'Send BCC copy of quote to this address',3,'email','a:0:{}'),(73,'system/general/app_name','CSBill','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(74,'system/general/logo','592b23d57e4c8.png','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(75,'quote/bcc_address','','Send BCC copy of quote to this address',NULL,'SymfonyComponentFormExtensionCoreTypeEmailType',NULL),(76,'quote/email_subject','New Quotation - #{id}','To include the id of the quote in the subject, add the placeholder {id} where you want the id',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(77,'invoice/bcc_address','','Send BCC copy of invoice to this address',NULL,'SymfonyComponentFormExtensionCoreTypeEmailType',NULL),(78,'invoice/email_subject','New Invoice - #{id}','To include the id of the invoice in the subject, add the placeholder {id} where you want the id',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(79,'email/format','both','In what format should emails be sent.',NULL,'SymfonyComponentFormExtensionCoreTypeRadioType',NULL),(80,'email/from_address','info@csbill.org','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(81,'email/from_name','CSBill','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(82,'hipchat/auth_token','','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(83,'hipchat/message_color','yellow','',NULL,'CSBillCoreBundleFormTypeSelect2Type',NULL),(84,'hipchat/notify','','',NULL,'SymfonyComponentFormExtensionCoreTypeCheckboxType',NULL),(85,'hipchat/room_id','','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(86,'hipchat/server_url','https://api.hipchat.com','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(87,'sms/twilio/number','','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(88,'sms/twilio/sid','','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(89,'sms/twilio/token','','',NULL,'SymfonyComponentFormExtensionCoreTypeTextType',NULL),(90,'notification/client_create','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(91,'notification/invoice_status_update','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(92,'notification/quote_status_update','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(93,'notification/payment_made','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(94,'notification/client_create','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(95,'notification/invoice_status_update','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(96,'notification/quote_status_update','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(97,'notification/payment_made','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(98,'notification/client_create','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(99,'notification/invoice_status_update','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(100,'notification/quote_status_update','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL),(101,'notification/payment_made','{\"email\":1,\"hipchat\":0,\"sms\":0}',NULL,NULL,'CSBillNotificationBundleFormTypeNotificationType',NULL);
/*!40000 ALTER TABLE `app_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_credit`
--

DROP TABLE IF EXISTS `client_credit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_credit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `value_amount` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `value_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4967254D19EB6921` (`client_id`),
  CONSTRAINT `FK_4967254D19EB6921` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_credit`
--

LOCK TABLES `client_credit` WRITE;
/*!40000 ALTER TABLE `client_credit` DISABLE KEYS */;
INSERT INTO `client_credit` VALUES (1,1,0,'2017-05-26 19:06:50','2017-05-26 19:06:50',NULL,NULL);
/*!40000 ALTER TABLE `client_credit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(125) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_C82E745E237E06` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,'active','One','https://google.com','2017-05-26 19:06:50','2017-05-26 19:06:50',NULL,NULL,'GBP');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_sections`
--

DROP TABLE IF EXISTS `config_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_965EAD465E237E06` (`name`),
  KEY `IDX_965EAD46727ACA70` (`parent_id`),
  CONSTRAINT `FK_965EAD46727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `config_sections` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_sections`
--

LOCK TABLES `config_sections` WRITE;
/*!40000 ALTER TABLE `config_sections` DISABLE KEYS */;
INSERT INTO `config_sections` VALUES (1,NULL,'system'),(2,1,'general'),(3,NULL,'quote'),(4,NULL,'invoice'),(5,NULL,'email'),(6,NULL,'hipchat'),(7,NULL,'sms'),(8,7,'twilio');
/*!40000 ALTER TABLE `config_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_details`
--

DROP TABLE IF EXISTS `contact_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) DEFAULT NULL,
  `contact_type_id` int(11) DEFAULT NULL,
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E8092A0BE7A1254A` (`contact_id`),
  KEY `IDX_E8092A0B5F63AD12` (`contact_type_id`),
  CONSTRAINT `FK_E8092A0B5F63AD12` FOREIGN KEY (`contact_type_id`) REFERENCES `contact_types` (`id`),
  CONSTRAINT `FK_E8092A0BE7A1254A` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_details`
--

LOCK TABLES `contact_details` WRITE;
/*!40000 ALTER TABLE `contact_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_types`
--

DROP TABLE IF EXISTS `contact_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL,
  `type` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `field_options` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_741A993F5E237E06` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_types`
--

LOCK TABLES `contact_types` WRITE;
/*!40000 ALTER TABLE `contact_types` DISABLE KEYS */;
INSERT INTO `contact_types` VALUES (1,'email',1,'email','a:1:{s:11:\"constraints\";a:1:{i:0;s:5:\"email\";}}'),(2,'mobile',0,'text','N;'),(3,'phone',0,'text','N;'),(4,'address',0,'textarea','N;');
/*!40000 ALTER TABLE `contact_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `firstname` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  `lastname` varchar(125) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3340157319EB6921` (`client_id`),
  KEY `email` (`email`),
  CONSTRAINT `FK_3340157319EB6921` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES (1,1,'OneS',NULL,'2017-05-26 19:06:50','2017-05-28 17:59:27',NULL,'one@one.com'),(2,1,'Two',NULL,'2017-05-26 19:06:50','2017-05-26 19:06:50','2017-05-26 19:18:44','two@two.com'),(3,1,'Two',NULL,'2017-05-28 17:59:21','2017-05-28 17:59:21',NULL,'two@two.com'),(4,1,'Three',NULL,'2017-05-28 18:01:38','2017-05-28 18:01:38',NULL,'three@three.com'),(5,1,'Four',NULL,'2017-05-28 18:04:55','2017-05-28 18:04:55',NULL,'four@four.com'),(6,1,'Four',NULL,'2017-05-28 18:07:48','2017-05-28 18:07:48',NULL,'four@four.com'),(7,1,'Five',NULL,'2017-05-28 18:14:18','2017-05-28 18:14:18',NULL,'five@five.com'),(8,1,'Six',NULL,'2017-05-28 18:14:42','2017-05-28 18:14:42',NULL,'six@six.com'),(9,1,'Seven',NULL,'2017-05-28 18:15:13','2017-05-28 18:15:13',NULL,'seven@seven.com'),(10,1,'Seven',NULL,'2017-05-28 18:17:28','2017-05-28 18:17:28',NULL,'seven@seven.com'),(11,1,'TenS',NULL,'2017-05-28 18:17:47','2017-05-28 18:17:54',NULL,'ten@ten.com');
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ext_log_entries`
--

DROP TABLE IF EXISTS `ext_log_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ext_log_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `logged_at` datetime NOT NULL,
  `object_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object_class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `version` int(11) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_class_lookup_idx` (`object_class`),
  KEY `log_date_lookup_idx` (`logged_at`),
  KEY `log_user_lookup_idx` (`username`),
  KEY `log_version_lookup_idx` (`object_id`,`object_class`,`version`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ext_log_entries`
--

LOCK TABLES `ext_log_entries` WRITE;
/*!40000 ALTER TABLE `ext_log_entries` DISABLE KEYS */;
INSERT INTO `ext_log_entries` VALUES (1,'create','2017-05-20 12:46:10','1','CSBill\\UserBundle\\Entity\\User',1,'N;',NULL),(2,'create','2017-05-22 06:36:34','2','CSBill\\UserBundle\\Entity\\User',1,'N;',NULL),(4,'create','2017-05-26 19:06:50','1','CSBill\\ClientBundle\\Entity\\Client',1,'N;','admin'),(5,'create','2017-05-26 19:06:50','1','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(6,'create','2017-05-26 19:06:50','2','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(7,'create','2017-05-26 19:06:50','1','CSBill\\ClientBundle\\Entity\\Credit',1,'N;','admin'),(8,'create','2017-05-28 17:59:21','3','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(9,'create','2017-05-28 18:01:38','4','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(10,'create','2017-05-28 18:04:59','5','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(11,'create','2017-05-28 18:07:48','6','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(12,'create','2017-05-28 18:14:18','7','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(13,'create','2017-05-28 18:14:42','8','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(14,'create','2017-05-28 18:15:13','9','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(15,'create','2017-05-28 18:17:28','10','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(16,'create','2017-05-28 18:17:47','11','CSBill\\ClientBundle\\Entity\\Contact',1,'N;','admin'),(17,'create','2017-05-28 18:37:10','1','CSBill\\QuoteBundle\\Entity\\Quote',1,'N;','admin'),(18,'create','2017-05-28 18:37:10','1','CSBill\\QuoteBundle\\Entity\\Item',1,'N;','admin'),(19,'create','2017-05-28 18:49:38','1','CSBill\\ClientBundle\\Entity\\Address',1,'N;','admin'),(20,'create','2017-05-28 18:49:38','2','CSBill\\ClientBundle\\Entity\\Address',1,'N;','admin'),(21,'create','2017-05-28 19:23:29','1','CSBill\\InvoiceBundle\\Entity\\Invoice',1,'N;','admin'),(22,'create','2017-05-28 19:23:29','1','CSBill\\InvoiceBundle\\Entity\\Item',1,'N;','admin'),(23,'create','2017-05-28 19:27:48','4','CSBill\\PaymentBundle\\Entity\\PaymentMethod',1,'N;','admin'),(24,'create','2017-05-28 19:28:29','1','CSBill\\UserBundle\\Entity\\ApiToken',1,'N;','admin'),(25,'create','2017-05-28 20:47:49','2','CSBill\\QuoteBundle\\Entity\\Quote',1,'N;','admin'),(26,'create','2017-05-28 20:47:49','2','CSBill\\QuoteBundle\\Entity\\Item',1,'N;','admin'),(27,'create','2017-05-29 18:54:23','1','CSBill\\TaxBundle\\Entity\\Tax',1,'N;','admin'),(28,'create','2017-05-29 18:57:12','2','CSBill\\UserBundle\\Entity\\ApiToken',1,'N;','admin');
/*!40000 ALTER TABLE `ext_log_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ext_translations`
--

DROP TABLE IF EXISTS `ext_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ext_translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locale` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `object_class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `field` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `foreign_key` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lookup_unique_idx` (`locale`,`object_class`,`field`,`foreign_key`),
  KEY `translations_lookup_idx` (`locale`,`object_class`,`foreign_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ext_translations`
--

LOCK TABLES `ext_translations` WRITE;
/*!40000 ALTER TABLE `ext_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ext_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_lines`
--

DROP TABLE IF EXISTS `invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `price_amount` int(11) NOT NULL,
  `qty` double NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `tax_id` int(11) DEFAULT NULL,
  `total_amount` int(11) NOT NULL,
  `price_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `total_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DCC4B9F82989F1FD` (`invoice_id`),
  KEY `IDX_DCC4B9F8B2A824D8` (`tax_id`),
  CONSTRAINT `FK_DCC4B9F82989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `FK_DCC4B9F8B2A824D8` FOREIGN KEY (`tax_id`) REFERENCES `tax_rates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_lines`
--

LOCK TABLES `invoice_lines` WRITE;
/*!40000 ALTER TABLE `invoice_lines` DISABLE KEYS */;
INSERT INTO `invoice_lines` VALUES (1,1,'One',20000,1,'2017-05-28 19:23:29','2017-05-28 19:23:29',NULL,NULL,20000,'GBP','GBP');
/*!40000 ALTER TABLE `invoice_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `total_amount` int(11) NOT NULL,
  `discount` double DEFAULT NULL,
  `due` date DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `users` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `baseTotal_amount` int(11) NOT NULL,
  `paid_date` datetime DEFAULT NULL,
  `uuid` varchar(36) COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:uuid)',
  `terms` longtext COLLATE utf8_unicode_ci,
  `notes` longtext COLLATE utf8_unicode_ci,
  `tax_amount` int(11) NOT NULL,
  `balance_amount` int(11) NOT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL,
  `total_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `baseTotal_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `balance_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tax_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6A2F2F9519EB6921` (`client_id`),
  CONSTRAINT `FK_6A2F2F9519EB6921` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'draft',1,18000,0.1,NULL,'2017-05-28 19:23:29','2017-05-28 19:23:29',NULL,'O:43:\"Doctrine\\Common\\Collections\\ArrayCollection\":1:{s:53:\"\0Doctrine\\Common\\Collections\\ArrayCollection\0elements\";a:2:{i:0;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:1;i:1;s:4:\"OneS\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-26 19:06:50.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:27.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:1;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":270:{a:5:{i:0;i:3;i:1;s:3:\"Two\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:21.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:21.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}}}',20000,NULL,'214897a6-43db-11e7-b633-67939ab7dd65',NULL,NULL,0,18000,NULL,0,'GBP','GBP','GBP','GBP');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration_versions`
--

DROP TABLE IF EXISTS `migration_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration_versions` (
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration_versions`
--

LOCK TABLES `migration_versions` WRITE;
/*!40000 ALTER TABLE `migration_versions` DISABLE KEYS */;
INSERT INTO `migration_versions` VALUES ('010'),('020'),('030'),('040'),('042'),('050'),('060'),('080'),('090'),('101'),('110');
/*!40000 ALTER TABLE `migration_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_event` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hipchat` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sms` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6000B0D3FD1AEF5E` (`notification_event`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'client_create','1','0','0'),(2,'invoice_status_update','1','0','0'),(3,'quote_status_update','1','0','0'),(4,'payment_made','1','0','0');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  `gateway_name` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  `config` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `internal` tinyint(1) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  `factory` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4FABF9833D4E91C8` (`gateway_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'Cash','cash','a:0:{}','2017-05-20 14:39:36','2017-05-20 14:39:36',NULL,1,1,'offline'),(2,'Bank Transfer','bank_transfer','a:0:{}','2017-05-20 14:39:36','2017-05-20 14:39:36',NULL,1,1,'offline'),(3,'Credit','credit','a:0:{}','2017-05-20 14:39:36','2017-05-20 14:39:36',NULL,1,1,'offline'),(4,'Paypal Express Checkout','paypal_express_checkout','a:4:{s:8:\"username\";s:3:\"one\";s:8:\"password\";s:3:\"two\";s:9:\"signature\";s:5:\"three\";s:7:\"sandbox\";b:1;}','2017-05-28 19:27:48','2017-05-28 19:27:48',NULL,NULL,1,'paypal_express_checkout');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `method_id` int(11) DEFAULT NULL,
  `status` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `total_amount` int(11) DEFAULT NULL,
  `currency_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `message` longtext COLLATE utf8_unicode_ci,
  `completed` datetime DEFAULT NULL,
  `client` int(11) DEFAULT NULL,
  `details` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  `number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `client_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `client_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_65D29B322989F1FD` (`invoice_id`),
  KEY `IDX_65D29B3219883967` (`method_id`),
  KEY `IDX_65D29B32C7440455` (`client`),
  CONSTRAINT `FK_65D29B3219883967` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`id`),
  CONSTRAINT `FK_65D29B322989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `FK_65D29B32C7440455` FOREIGN KEY (`client`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quote_lines`
--

DROP TABLE IF EXISTS `quote_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quote_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci NOT NULL,
  `price_amount` int(11) NOT NULL,
  `qty` double NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `tax_id` int(11) DEFAULT NULL,
  `total_amount` int(11) NOT NULL,
  `price_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `total_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_ECE1642CDB805178` (`quote_id`),
  KEY `IDX_ECE1642CB2A824D8` (`tax_id`),
  CONSTRAINT `FK_ECE1642CB2A824D8` FOREIGN KEY (`tax_id`) REFERENCES `tax_rates` (`id`),
  CONSTRAINT `FK_ECE1642CDB805178` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quote_lines`
--

LOCK TABLES `quote_lines` WRITE;
/*!40000 ALTER TABLE `quote_lines` DISABLE KEYS */;
INSERT INTO `quote_lines` VALUES (1,1,'One',10000,1,'2017-05-28 18:37:10','2017-05-28 18:37:10',NULL,NULL,10000,'GBP','GBP'),(2,2,'One',10000,1,'2017-05-28 20:47:49','2017-05-28 20:47:49',NULL,NULL,10000,'GBP','GBP');
/*!40000 ALTER TABLE `quote_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotes`
--

DROP TABLE IF EXISTS `quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `total_amount` int(11) NOT NULL,
  `discount` double DEFAULT NULL,
  `due` date DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `users` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `baseTotal_amount` int(11) NOT NULL,
  `uuid` varchar(36) COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:uuid)',
  `terms` longtext COLLATE utf8_unicode_ci,
  `notes` longtext COLLATE utf8_unicode_ci,
  `tax_amount` int(11) NOT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `total_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `baseTotal_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tax_currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A1B588C519EB6921` (`client_id`),
  CONSTRAINT `FK_A1B588C519EB6921` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotes`
--

LOCK TABLES `quotes` WRITE;
/*!40000 ALTER TABLE `quotes` DISABLE KEYS */;
INSERT INTO `quotes` VALUES (1,'pending',1,9000,0.1,NULL,'2017-05-28 18:37:10','2017-05-28 20:47:49',NULL,'O:43:\"Doctrine\\Common\\Collections\\ArrayCollection\":1:{s:53:\"\0Doctrine\\Common\\Collections\\ArrayCollection\0elements\";a:10:{i:0;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:1;i:1;s:4:\"OneS\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-26 19:06:50.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:27.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:1;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":270:{a:5:{i:0;i:3;i:1;s:3:\"Two\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:21.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:21.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:2;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":272:{a:5:{i:0;i:4;i:1;s:5:\"Three\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:01:38.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:01:38.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:3;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:5;i:1;s:4:\"Four\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:04:55.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:04:55.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:4;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:6;i:1;s:4:\"Four\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:07:48.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:07:48.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:5;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:7;i:1;s:4:\"Five\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:18.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:18.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:6;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":270:{a:5:{i:0;i:8;i:1;s:3:\"Six\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:42.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:42.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:7;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":272:{a:5:{i:0;i:9;i:1;s:5:\"Seven\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:15:13.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:15:13.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:8;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":273:{a:5:{i:0;i:10;i:1;s:5:\"Seven\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:28.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:28.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:9;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":272:{a:5:{i:0;i:11;i:1;s:4:\"TenS\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:47.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:54.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}}}',10000,'a8a1e0a6-43d4-11e7-8afe-43919cd3b76d',NULL,NULL,0,NULL,'GBP','GBP','GBP'),(2,'draft',1,9000,0.1,NULL,'2017-05-28 20:47:49','2017-05-28 20:47:49',NULL,'O:43:\"Doctrine\\Common\\Collections\\ArrayCollection\":1:{s:53:\"\0Doctrine\\Common\\Collections\\ArrayCollection\0elements\";a:10:{i:0;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:1;i:1;s:4:\"OneS\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-26 19:06:50.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:27.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:1;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":270:{a:5:{i:0;i:3;i:1;s:3:\"Two\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:21.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 17:59:21.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:2;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":272:{a:5:{i:0;i:4;i:1;s:5:\"Three\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:01:38.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:01:38.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:3;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:5;i:1;s:4:\"Four\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:04:55.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:04:55.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:4;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:6;i:1;s:4:\"Four\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:07:48.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:07:48.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:5;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":271:{a:5:{i:0;i:7;i:1;s:4:\"Five\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:18.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:18.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:6;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":270:{a:5:{i:0;i:8;i:1;s:3:\"Six\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:42.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:14:42.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:7;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":272:{a:5:{i:0;i:9;i:1;s:5:\"Seven\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:15:13.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:15:13.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:8;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":273:{a:5:{i:0;i:10;i:1;s:5:\"Seven\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:28.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:28.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}i:9;C:34:\"CSBill\\ClientBundle\\Entity\\Contact\":272:{a:5:{i:0;i:11;i:1;s:4:\"TenS\";i:2;N;i:3;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:47.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}i:4;O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2017-05-28 18:17:54.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}}}}}',10000,'e91f788e-43e6-11e7-ab1b-af0cf61f0efa',NULL,NULL,0,NULL,'GBP','GBP','GBP');
/*!40000 ALTER TABLE `quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurring_invoices`
--

DROP TABLE IF EXISTS `recurring_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recurring_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `frequency` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_start` date NOT NULL,
  `date_end` date DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_FE93E2842989F1FD` (`invoice_id`),
  CONSTRAINT `FK_FE93E2842989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurring_invoices`
--

LOCK TABLES `recurring_invoices` WRITE;
/*!40000 ALTER TABLE `recurring_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurring_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_token`
--

DROP TABLE IF EXISTS `security_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_token` (
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `details` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:object)',
  `after_url` longtext COLLATE utf8_unicode_ci,
  `target_url` longtext COLLATE utf8_unicode_ci NOT NULL,
  `gateway_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_token`
--

LOCK TABLES `security_token` WRITE;
/*!40000 ALTER TABLE `security_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `rate` double NOT NULL,
  `tax_type` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rates`
--

LOCK TABLES `tax_rates` WRITE;
/*!40000 ALTER TABLE `tax_rates` DISABLE KEYS */;
INSERT INTO `tax_rates` VALUES (1,'VAT',0.14,'inclusive','2017-05-29 18:54:23','2017-05-29 18:54:23',NULL);
/*!40000 ALTER TABLE `tax_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `username_canonical` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_canonical` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `confirmation_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_requested_at` datetime DEFAULT NULL,
  `roles` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  `mobile` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  `expired` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `credentials_expired` tinyint(1) NOT NULL,
  `credentials_expire_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E992FC23A8` (`username_canonical`),
  UNIQUE KEY `UNIQ_1483A5E9A0D96FBF` (`email_canonical`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin',NULL,'$2y$10$f2qLbVNddJ/cIozaSr/iIu4cY4JsOqfRoJDRPqnLW5hsYFnFp4RVO','foo@bar.com',1,'admin','foo@bar.com','2017-05-29 19:38:46',NULL,NULL,'a:1:{i:0;s:16:\"ROLE_SUPER_ADMIN\";}','2017-05-20 12:46:10','2017-05-29 19:38:46',NULL,NULL,0,0,NULL,0,NULL),(2,'testuser',NULL,'$2y$10$iwgKokBbMDboCC3vlRXpyeYwnoI2ASxML0eJvC39swkmngsYigpZS','testuser@local.dev',1,'testuser','testuser@local.dev',NULL,NULL,NULL,'a:1:{i:0;s:16:\"ROLE_SUPER_ADMIN\";}','2017-05-22 06:36:34','2017-05-22 06:36:34',NULL,NULL,0,0,NULL,0,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `version`
--

DROP TABLE IF EXISTS `version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `version` (
  `version` varchar(125) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `version`
--

LOCK TABLES `version` WRITE;
/*!40000 ALTER TABLE `version` DISABLE KEYS */;
INSERT INTO `version` VALUES ('2.0.0-dev');
/*!40000 ALTER TABLE `version` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-05-31 20:22:57
