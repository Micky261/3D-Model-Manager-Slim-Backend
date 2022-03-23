CREATE TABLE `sessions`
(
    `id`         bigint(20) UNSIGNED NOT NULL,
    `user_id`    bigint(20) UNSIGNED NOT NULL,
    `session_id` varchar(128)        NOT NULL,
    `rights`     text                         DEFAULT NULL,
    `created_at` timestamp           NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `sessions`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `sessions_token_unique` (`session_id`);

ALTER TABLE `sessions`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
