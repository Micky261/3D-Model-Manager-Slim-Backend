ALTER TABLE `model_files`
    ADD storage VARCHAR(255) DEFAULT 'Default' NOT NULL AFTER id;
