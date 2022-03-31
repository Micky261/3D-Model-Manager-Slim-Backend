CREATE TABLE `models`
(
    `id`                   bigint(20) UNSIGNED                   NOT NULL,
    `user_id`              bigint(20) UNSIGNED                   NOT NULL,
    `name`                 varchar(255)                          NOT NULL DEFAULT '',
    `imported_name`        varchar(255)                                   DEFAULT NULL,
    `description`          text                                           DEFAULT NULL,
    `imported_description` text                                           DEFAULT NULL,
    `notes`                text                                           DEFAULT NULL,
    `links`                longtext                                       DEFAULT NULL CHECK (json_valid(`links`)),
    `favorite`             bool                                  NOT NULL DEFAULT false,
    `author`               varchar(255)                          NOT NULL DEFAULT '',
    `imported_author`      varchar(255)                                   DEFAULT NULL,
    `licence`              varchar(255)                                   DEFAULT NULL,
    `imported_licence`     varchar(255)                                   DEFAULT NULL,
    `import_source`        varchar(255)                                   DEFAULT NULL,
    `created_at`           timestamp                             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           timestamp ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `models`
    ADD PRIMARY KEY (`id`),
    ADD KEY `models_user_id_foreign` (`user_id`);

ALTER TABLE `models`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `models`
    ADD CONSTRAINT `models_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
