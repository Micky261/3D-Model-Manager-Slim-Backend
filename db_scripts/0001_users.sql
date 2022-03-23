CREATE TABLE `users`
(
    `id`                bigint(20) UNSIGNED NOT NULL,
    `name`              varchar(255)        NOT NULL,
    `email`             varchar(255)        NOT NULL,
    `email_verified_at` timestamp           NULL     DEFAULT NULL,
    `password`          varchar(255)        NOT NULL,
    `created_at`        timestamp           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        timestamp           NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `users`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `users_email_unique` (`email`);

ALTER TABLE `users`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
