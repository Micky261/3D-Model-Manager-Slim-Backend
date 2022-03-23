CREATE TABLE `password_resets`
(
    `email`      varchar(255) NOT NULL,
    `token`      varchar(255) NOT NULL,
    `created_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `password_resets`
    ADD KEY `password_resets_email_index` (`email`);
