-- =============================================================================
-- Vendaffacil — schema MySQL alinhado às migrations em database/migrations/
-- =============================================================================
--
-- Conexão Laravel (.env):
--   DB_CONNECTION=mysql
--   DB_HOST=186.209.113.112
--   DB_PORT=3306
--   DB_DATABASE=grup1285_vendaffacil
--   DB_USERNAME=grup1285_userisrael
--   DB_PASSWORD=...   (defina só no .env; não coloque senha neste ficheiro no Git)
--
-- Preferível: com o .env correto, rode `php artisan migrate` (cria/atualiza tabelas).
-- Use este SQL se precisar importar no phpMyAdmin / cliente MySQL sem Artisan.
--
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `grup1285_vendaffacil`;

DROP TABLE IF EXISTS `pedido_itens`;
DROP TABLE IF EXISTS `pedidos`;
DROP TABLE IF EXISTS `suporte_ticket_mensagens`;
DROP TABLE IF EXISTS `ve_acertos`;
DROP TABLE IF EXISTS `ve_venda_externa_registros`;
DROP TABLE IF EXISTS `ve_fiados`;
DROP TABLE IF EXISTS `ve_remessas`;
DROP TABLE IF EXISTS `ve_pontos`;
DROP TABLE IF EXISTS `caixa_movimentos`;
DROP TABLE IF EXISTS `caixa_turnos`;
DROP TABLE IF EXISTS `financeiro_titulos`;
DROP TABLE IF EXISTS `fidelidade_cartoes`;
DROP TABLE IF EXISTS `fidelidade_programas`;
DROP TABLE IF EXISTS `produtos`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `suporte_tickets`;
DROP TABLE IF EXISTS `assinaturas`;
DROP TABLE IF EXISTS `empresas`;
DROP TABLE IF EXISTS `modulos`;
DROP TABLE IF EXISTS `planos`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned DEFAULT NULL,
  `role` varchar(32) NOT NULL DEFAULT 'operador',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `planos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `preco_mensal` decimal(10,2) NOT NULL,
  `feature_primaria` varchar(255) NOT NULL,
  `feature_secundaria` varchar(255) NOT NULL,
  `ordem` tinyint unsigned NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modulos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `categoria` varchar(255) NOT NULL DEFAULT '',
  `situacao` varchar(32) NOT NULL,
  `ordem` tinyint unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assinaturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned DEFAULT NULL,
  `empresa_nome` varchar(255) NOT NULL,
  `plano_id` bigint unsigned DEFAULT NULL,
  `valor_mensal` decimal(10,2) NOT NULL,
  `proxima_cobranca` date NOT NULL,
  `gateway` varchar(255) DEFAULT NULL,
  `status` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assinaturas_plano_id_foreign` (`plano_id`),
  KEY `assinaturas_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `empresas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `slug` varchar(64) DEFAULT NULL,
  `email_contato` varchar(255) DEFAULT NULL,
  `cnpj` varchar(32) DEFAULT NULL,
  `plano_id` bigint unsigned DEFAULT NULL,
  `status` varchar(32) NOT NULL,
  `modulos_resumo` varchar(255) DEFAULT NULL,
  `cliente_desde` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresas_slug_unique` (`slug`),
  KEY `empresas_plano_id_foreign` (`plano_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users`
  ADD CONSTRAINT `users_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;

ALTER TABLE `assinaturas`
  ADD CONSTRAINT `assinaturas_plano_id_foreign` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assinaturas_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;

ALTER TABLE `empresas`
  ADD CONSTRAINT `empresas_plano_id_foreign` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE SET NULL;

CREATE TABLE `suporte_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned DEFAULT NULL,
  `assunto` varchar(255) NOT NULL,
  `descricao` text,
  `prioridade` varchar(32) NOT NULL,
  `status` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suporte_tickets_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `suporte_tickets`
  ADD CONSTRAINT `suporte_tickets_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;

CREATE TABLE `categorias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nome` varchar(255) NOT NULL,
  `ordem` smallint unsigned NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categorias_empresa_id_nome_unique` (`empresa_id`,`nome`),
  KEY `categorias_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `produtos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `categoria_id` bigint unsigned DEFAULT NULL,
  `sku` varchar(64) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `estoque` int unsigned NOT NULL DEFAULT 0,
  `descricao` text,
  `visivel_loja` tinyint(1) NOT NULL DEFAULT 1,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `produtos_empresa_id_sku_unique` (`empresa_id`,`sku`),
  KEY `produtos_empresa_id_foreign` (`empresa_id`),
  KEY `produtos_categoria_id_foreign` (`categoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produtos_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(32) DEFAULT NULL,
  `documento` varchar(32) DEFAULT NULL,
  `observacoes` text,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clientes_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

CREATE TABLE `fidelidade_programas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 0,
  `nome_exibicao` varchar(120) NOT NULL DEFAULT 'Cartão fidelidade',
  `pedidos_meta` smallint unsigned NOT NULL DEFAULT 10,
  `tipo_recompensa` varchar(32) NOT NULL,
  `produto_id` bigint unsigned DEFAULT NULL,
  `valor_desconto` decimal(10,2) DEFAULT NULL,
  `texto_recompensa` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fidelidade_programas_empresa_id_unique` (`empresa_id`),
  KEY `fidelidade_programas_produto_id_foreign` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `fidelidade_programas`
  ADD CONSTRAINT `fidelidade_programas_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fidelidade_programas_produto_id_foreign` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE SET NULL;

CREATE TABLE `fidelidade_cartoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `telefone_normalizado` varchar(20) NOT NULL,
  `cliente_id` bigint unsigned DEFAULT NULL,
  `selos` int unsigned NOT NULL DEFAULT 0,
  `total_resgates` int unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fidelidade_cartoes_empresa_id_telefone_normalizado_unique` (`empresa_id`,`telefone_normalizado`),
  KEY `fidelidade_cartoes_empresa_id_foreign` (`empresa_id`),
  KEY `fidelidade_cartoes_cliente_id_foreign` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `fidelidade_cartoes`
  ADD CONSTRAINT `fidelidade_cartoes_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fidelidade_cartoes_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

CREATE TABLE `financeiro_titulos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `tipo` varchar(16) NOT NULL,
  `contraparte` varchar(255) DEFAULT NULL,
  `descricao` varchar(500) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `vencimento` date NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'aberto',
  `pago_em` date DEFAULT NULL,
  `observacoes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financeiro_titulos_empresa_id_tipo_status_index` (`empresa_id`,`tipo`,`status`),
  KEY `financeiro_titulos_empresa_id_vencimento_index` (`empresa_id`,`vencimento`),
  KEY `financeiro_titulos_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `financeiro_titulos`
  ADD CONSTRAINT `financeiro_titulos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

CREATE TABLE `caixa_turnos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `aberto_em` datetime NOT NULL,
  `fechado_em` datetime DEFAULT NULL,
  `valor_abertura` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_conferido_fechamento` decimal(12,2) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'aberto',
  `obs_abertura` text,
  `obs_fechamento` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `caixa_turnos_empresa_id_status_index` (`empresa_id`,`status`),
  KEY `caixa_turnos_empresa_id_foreign` (`empresa_id`),
  KEY `caixa_turnos_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `caixa_turnos`
  ADD CONSTRAINT `caixa_turnos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `caixa_turnos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `caixa_movimentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `caixa_turno_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `tipo` varchar(32) NOT NULL,
  `descricao` varchar(500) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `caixa_movimentos_caixa_turno_id_index` (`caixa_turno_id`),
  KEY `caixa_movimentos_caixa_turno_id_foreign` (`caixa_turno_id`),
  KEY `caixa_movimentos_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `caixa_movimentos`
  ADD CONSTRAINT `caixa_movimentos_caixa_turno_id_foreign` FOREIGN KEY (`caixa_turno_id`) REFERENCES `caixa_turnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `caixa_movimentos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `ve_pontos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `nome` varchar(255) NOT NULL,
  `regiao` varchar(255) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'ativo',
  `proximo_acerto_em` datetime DEFAULT NULL,
  `ultimo_acerto_em` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ve_pontos_empresa_id_status_index` (`empresa_id`,`status`),
  KEY `ve_pontos_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ve_pontos`
  ADD CONSTRAINT `ve_pontos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

CREATE TABLE `ve_remessas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `ve_ponto_id` bigint unsigned DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'preparacao',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ve_remessas_empresa_id_status_index` (`empresa_id`,`status`),
  KEY `ve_remessas_empresa_id_foreign` (`empresa_id`),
  KEY `ve_remessas_ve_ponto_id_foreign` (`ve_ponto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ve_remessas`
  ADD CONSTRAINT `ve_remessas_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ve_remessas_ve_ponto_id_foreign` FOREIGN KEY (`ve_ponto_id`) REFERENCES `ve_pontos` (`id`) ON DELETE SET NULL;

CREATE TABLE `ve_fiados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `ve_ponto_id` bigint unsigned DEFAULT NULL,
  `contraparte` varchar(255) DEFAULT NULL,
  `descricao` varchar(500) NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'aberto',
  `vencimento` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ve_fiados_empresa_id_status_index` (`empresa_id`,`status`),
  KEY `ve_fiados_empresa_id_foreign` (`empresa_id`),
  KEY `ve_fiados_ve_ponto_id_foreign` (`ve_ponto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ve_fiados`
  ADD CONSTRAINT `ve_fiados_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ve_fiados_ve_ponto_id_foreign` FOREIGN KEY (`ve_ponto_id`) REFERENCES `ve_pontos` (`id`) ON DELETE SET NULL;

CREATE TABLE `ve_venda_externa_registros` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `ve_ponto_id` bigint unsigned NOT NULL,
  `data_venda` date NOT NULL,
  `valor` decimal(12,2) NOT NULL,
  `referencia` varchar(120) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ve_venda_externa_registros_empresa_id_data_venda_index` (`empresa_id`,`data_venda`),
  KEY `ve_venda_externa_registros_empresa_id_foreign` (`empresa_id`),
  KEY `ve_venda_externa_registros_ve_ponto_id_foreign` (`ve_ponto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ve_venda_externa_registros`
  ADD CONSTRAINT `ve_venda_externa_registros_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ve_venda_externa_registros_ve_ponto_id_foreign` FOREIGN KEY (`ve_ponto_id`) REFERENCES `ve_pontos` (`id`) ON DELETE CASCADE;

CREATE TABLE `ve_acertos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `ve_ponto_id` bigint unsigned NOT NULL,
  `ve_remessa_id` bigint unsigned DEFAULT NULL,
  `data_acerto` date DEFAULT NULL,
  `valor_vendas` decimal(12,2) DEFAULT NULL,
  `valor_repasse` decimal(12,2) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'aberto',
  `observacoes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ve_acertos_empresa_id_status_index` (`empresa_id`,`status`),
  KEY `ve_acertos_empresa_id_data_acerto_index` (`empresa_id`,`data_acerto`),
  KEY `ve_acertos_empresa_id_foreign` (`empresa_id`),
  KEY `ve_acertos_ve_ponto_id_foreign` (`ve_ponto_id`),
  KEY `ve_acertos_ve_remessa_id_foreign` (`ve_remessa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ve_acertos`
  ADD CONSTRAINT `ve_acertos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ve_acertos_ve_ponto_id_foreign` FOREIGN KEY (`ve_ponto_id`) REFERENCES `ve_pontos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ve_acertos_ve_remessa_id_foreign` FOREIGN KEY (`ve_remessa_id`) REFERENCES `ve_remessas` (`id`) ON DELETE SET NULL;

CREATE TABLE `suporte_ticket_mensagens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `suporte_ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `corpo` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suporte_ticket_mensagens_suporte_ticket_id_foreign` (`suporte_ticket_id`),
  KEY `suporte_ticket_mensagens_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `suporte_ticket_mensagens`
  ADD CONSTRAINT `suporte_ticket_mensagens_suporte_ticket_id_foreign` FOREIGN KEY (`suporte_ticket_id`) REFERENCES `suporte_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `suporte_ticket_mensagens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

CREATE TABLE `pedidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` bigint unsigned NOT NULL,
  `codigo_publico` varchar(32) NOT NULL,
  `canal` varchar(32) NOT NULL DEFAULT 'loja',
  `cliente_nome` varchar(120) NOT NULL,
  `cliente_telefone` varchar(32) NOT NULL,
  `cliente_email` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) NOT NULL,
  `complemento` varchar(120) DEFAULT NULL,
  `forma_pagamento` varchar(32) NOT NULL,
  `observacoes` text,
  `status` varchar(32) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `taxa_entrega` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pedidos_codigo_publico_unique` (`codigo_publico`),
  KEY `pedidos_empresa_id_status_index` (`empresa_id`,`status`),
  KEY `pedidos_empresa_id_created_at_index` (`empresa_id`,`created_at`),
  KEY `pedidos_empresa_id_foreign` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_empresa_id_foreign` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

CREATE TABLE `pedido_itens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` bigint unsigned NOT NULL,
  `produto_id` bigint unsigned DEFAULT NULL,
  `nome_produto` varchar(255) NOT NULL,
  `preco_unitario` decimal(12,2) NOT NULL,
  `quantidade` int unsigned NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_itens_pedido_id_foreign` (`pedido_id`),
  KEY `pedido_itens_produto_id_foreign` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `pedido_itens_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_itens_produto_id_foreign` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE SET NULL;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_04_04_120000_create_planos_table', 1),
('2026_04_04_130000_create_modulos_table', 1),
('2026_04_04_140000_create_assinaturas_table', 1),
('2026_04_04_150000_create_empresas_table', 1),
('2026_04_04_160000_add_empresa_id_to_users_table', 1),
('2026_04_04_170000_create_suporte_tickets_table', 1),
('2026_04_04_180000_add_empresa_id_to_assinaturas_table', 1),
('2026_04_04_190000_create_produtos_table', 1),
('2026_04_04_200000_create_categorias_table', 1),
('2026_04_04_200001_add_categoria_id_to_produtos_table', 1),
('2026_04_04_210000_create_clientes_table', 1),
('2026_04_05_100000_add_slug_to_empresas_table', 1),
('2026_04_05_101000_create_fidelidade_programas_table', 1),
('2026_04_05_102000_create_fidelidade_cartoes_table', 1),
('2026_04_05_120000_create_financeiro_titulos_table', 1),
('2026_04_05_140000_create_caixa_turnos_table', 1),
('2026_04_05_141000_create_caixa_movimentos_table', 1),
('2026_04_05_160000_create_ve_pontos_table', 1),
('2026_04_05_161000_create_ve_remessas_table', 1),
('2026_04_05_162000_create_ve_fiados_table', 1),
('2026_04_05_163000_create_ve_venda_externa_registros_table', 1),
('2026_04_05_200000_create_ve_acertos_table', 1),
('2026_04_06_120000_create_suporte_ticket_mensagens_table', 1),
('2026_04_06_140000_add_role_to_users_table', 1),
('2026_04_06_200000_create_pedidos_table', 1),
('2026_04_06_200001_create_pedido_itens_table', 1);

SET FOREIGN_KEY_CHECKS = 1;
