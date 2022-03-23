CREATE TABLE `model_tags`
(
    `user_id`  bigint(20) UNSIGNED NOT NULL,
    `model_id` bigint(20) UNSIGNED NOT NULL,
    `tag`      varchar(255)        NOT NULL
);

ALTER TABLE `model_tags`
    ADD PRIMARY KEY (`user_id`, `model_id`, `tag`),
    ADD KEY `model_tags_user_id_foreign` (`user_id`),
    ADD KEY `model_tags_model_id_foreign` (`model_id`),
    ADD KEY `model_tags_tag` (`tag`);

ALTER TABLE `model_tags`
    ADD CONSTRAINT `model_tags_model_id_foreign` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`),
    ADD CONSTRAINT `model_tags_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;
