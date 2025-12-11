<?php
class FreeEmailTestModel {
    private $mysqli;
    
    private $freeEmailDomains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
        'aol.com', 'icloud.com', 'mail.com', 'protonmail.com', 'tutanota.com',
        'yandex.com', 'zoho.com', 'fastmail.com', 'gmx.com', 'hushmail.com',
        'mailinator.com', 'guerrillamail.com', '10minutemail.com', 'tempmail.org',
        'dispostable.com', 'sharklasers.com', 'getairmail.com', 'mailnesia.com',
        'maildrop.cc', 'mailinator.net', 'guerrillamailblock.com', 'pokemail.net',
        'spam4.me', 'bccto.me', 'chacuo.net', 'dispostable.com', 'fakeinbox.com',
        'fakemailgenerator.com', 'mailmetrash.com', 'mailnull.com', 'mintemail.com',
        'mytrashmail.com', 'nwytg.net', 'sharklasers.com', 'spamspot.com',
        'tempr.email', 'throwawaymail.com', 'trashmail.net', 'wegwerfemail.de',
        'wegwerfemailadresse.com', 'yopmail.com', 'yopmail.net', 'yopmail.org'
    ];
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function testEmail($email) {
        if (empty($email)) {
            return ['error' => 'Email address is required.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email format provided.'];
        }
        
        $email = strtolower(trim($email));
        $domain = substr(strrchr($email, "@"), 1);
        
        $isFreeEmail = in_array($domain, $this->freeEmailDomains);
        
        if (!$isFreeEmail && $this->mysqli && !$this->mysqli->connect_errno) {
            try {
                $stmt = $this->mysqli->prepare("SELECT 1 FROM free_email_domains WHERE domain = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $domain);
                    $stmt->execute();
                    $stmt->store_result();
                    
                    if ($stmt->num_rows > 0) {
                        $isFreeEmail = true;
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                error_log("Free email test database error: " . $e->getMessage());
            }
        }
        
        $result = [
            'email' => $email,
            'domain' => $domain,
            'is_free_email' => $isFreeEmail,
            'provider_type' => $isFreeEmail ? 'Free Email Provider' : 'Custom/Corporate Domain',
            'message' => $isFreeEmail ? 
                "This is a free email provider: $domain" : 
                "This looks like a custom or corporate email domain: $domain",
            'recommendation' => $isFreeEmail ? 
                'Consider implementing additional verification for free email addresses.' : 
                'This domain appears to be legitimate and business-related.'
        ];
        
        return $result;
    }
}
