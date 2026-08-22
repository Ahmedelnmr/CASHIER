<?php
/**
 * Response Helper
 * JSON response utilities for AJAX endpoints
 */

class Response {
    
    /**
     * Send JSON success response
     */
    public static function json($data, $statusCode = 200) {
        // Clear any previous output or warnings
        if (ob_get_length()) ob_clean();
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success') {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $statusCode = 400) {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $statusCode);
    }

    /**
     * Send validation error
     */
    public static function validationError($errors) {
        self::json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }
}
