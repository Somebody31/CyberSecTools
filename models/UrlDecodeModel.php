<?php
class UrlDecodeModel {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function decode($inputString) {
        if (empty($inputString)) {
            return ['error' => 'No input provided'];
        }
        
        try {
            $decoded = urldecode($inputString);
            
            return [
                'original' => $inputString,
                'decoded' => $decoded,
                'message' => $decoded === $inputString ?
                    'Input appears to be already decoded or does not contain URL-encoded characters' :
                    'Input processed successfully'
            ];
        } catch (Exception $e) {
            return ['error' => 'Failed to process input: ' . $e->getMessage()];
        }
    }
}
