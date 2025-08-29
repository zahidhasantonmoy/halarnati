<?php

// Custom error handler function
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // This error code is not included in error_reporting
    if (!(error_reporting() & $errno)) {
        return false;
    }

    switch ($errno) {
        case E_USER_ERROR:
            $type = "Fatal Error";
            break;
        case E_USER_WARNING:
            $type = "Warning";
            break;
        case E_USER_NOTICE:
            $type = "Notice";
            break;
        default:
            $type = "Unknown Error";
            break;
    }

    $error_message = "[" . date("Y-m-d H:i:s") . "] " . $type . ": " . $errstr . " in " . $errfile . " on line " . $errline . "\n";

    // Log the error to the file configured in php.ini or config.php
    error_log($error_message);

    // For production, display a generic error message to the user
    if (ini_get('display_errors') == 0) {
        // In a production environment, avoid outputting sensitive error details.
        // Instead, you might redirect to a generic error page or simply exit.
        // For now, we'll just exit to prevent further execution and output.
        // You could also log the error to a database and display a user-friendly message.
        exit();
    } else {
        // For development, display the error details
        echo "<div style=\"text-align: left; padding: 15px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 5px; margin: 20px;\"><b>" . $type . ":</b> " . $errstr . "<br><b>File:</b> " . $errfile . "<br><b>Line:</b> " . $errline . "</div>";
    }

    // Don't execute PHP's internal error handler
    return true;
}

// Register the custom error handler
set_error_handler("customErrorHandler");

// Set default timezone to avoid PHP warnings
date_default_timezone_set('Asia/Dhaka'); // Or your preferred timezone

?>