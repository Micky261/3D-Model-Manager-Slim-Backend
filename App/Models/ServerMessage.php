<?php

namespace App\Models;

class ServerMessage {
    public function __construct(
        public string $message,
        public string $message_code,
        public int    $model_id = 0,
        public mixed  $additional_information = "" // Array or String depending on use case
    ) {
    }

    public static function unknownError(string $classN = "x", int $line = -1): ServerMessage {
        return new ServerMessage("Unknown error: $classN, $line", "UNKNOWN_ERROR");
    }

    public function toJson(): string {
        return json_encode(get_object_vars($this));
    }
}
