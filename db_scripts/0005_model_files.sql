CREATE TABLE `model_files`
(
    `id`         bigint(20) UNSIGNED NOT NULL,
    `user_id`    bigint(20) UNSIGNED NOT NULL,
    `model_id`   bigint(20) UNSIGNED NOT NULL,
    `type`       varchar(255)        NOT NULL,
    `filename`   varchar(255)        NOT NULL,
    `position`   int(11)             NOT NULL,
    `size`       int(11)             NOT NULL,
    `created_at` timestamp           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp           NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `model_files`
    ADD PRIMARY KEY (`id`),
    ADD KEY `model_files_user_id_foreign` (`user_id`),
    ADD KEY `model_files_model_id_foreign` (`model_id`);

ALTER TABLE `model_files`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `model_files`
    ADD CONSTRAINT `model_files_model_id_foreign` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`),
    ADD CONSTRAINT `model_files_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
