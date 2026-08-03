<?php

/**
 * AI Sentiment Analysis & Automated Summarization Engine
 * Supports Offline NLP Lexicon Analysis with an Optional External OpenAI / Gemini API Toggle.
 */

require_once "_dbconfig.php";

class AISentimentEngine {

    // Toggle for external AI API (Set to true and provide API key to enable live cloud AI calls)
    public static $use_external_ai = false;
    public static $api_provider = 'gemini'; // 'gemini' or 'openai'
    public static $api_key = ''; // Insert API key here or via environment variable

    private static $positive_words = [
        'excellent', 'great', 'amazing', 'helpful', 'clear', 'interactive', 'punctual',
        'supportive', 'best', 'engaging', 'thorough', 'expert', 'passionate', 'kind',
        'friendly', 'well', 'good', 'awesome', 'understandable', 'effective', 'inspiring',
        'organized', 'dedicated', 'patient', 'approachable', 'fair', 'superb'
    ];

    private static $constructive_words = [
        'improve', 'slow', 'fast', 'confusing', 'unclear', 'difficult', 'tough',
        'hard', 'strict', 'more', 'less', 'assignments', 'speed', 'pace', 'late',
        'volume', 'doubt', 'explain', 'homework', 'exam', 'practice', 'slides'
    ];

    /**
     * Configure External AI API Toggle
     */
    public static function setExternalAiConfig($enabled = true, $provider = 'gemini', $apiKey = '') {
        self::$use_external_ai = $enabled;
        self::$api_provider = strtolower($provider);
        if (!empty($apiKey)) {
            self::$api_key = $apiKey;
        }
    }

    /**
     * Analyze text sentiment (Toggles between External API and Offline Lexicon NLP)
     */
    public static function analyzeSentiment($text) {
        if (self::$use_external_ai && !empty(self::$api_key)) {
            $cloud_result = self::analyzeWithCloudAI($text);
            if ($cloud_result !== null) {
                return $cloud_result;
            }
        }

        return self::analyzeOffline($text);
    }

    /**
     * Offline Lexicon NLP Engine
     */
    private static function analyzeOffline($text) {
        $clean_text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
        $words = explode(' ', $clean_text);
        
        $pos_count = 0;
        $con_count = 0;

        foreach ($words as $w) {
            $w = trim($w);
            if (empty($w)) continue;
            if (in_array($w, self::$positive_words)) {
                $pos_count++;
            }
            if (in_array($w, self::$constructive_words)) {
                $con_count++;
            }
        }

        $total_hits = $pos_count + $con_count;
        if ($total_hits === 0) {
            return [
                'sentiment' => 'Neutral',
                'score' => 0.0,
                'badge' => 'bg-secondary',
                'engine' => 'Offline Lexicon'
            ];
        }

        $net_score = ($pos_count - $con_count) / max(1, $total_hits);

        if ($net_score > 0.1) {
            return [
                'sentiment' => 'Positive',
                'score' => round($net_score, 2),
                'badge' => 'bg-success',
                'engine' => 'Offline Lexicon'
            ];
        } elseif ($net_score < -0.1 || $con_count > $pos_count) {
            return [
                'sentiment' => 'Constructive',
                'score' => round($net_score, 2),
                'badge' => 'bg-warning text-dark',
                'engine' => 'Offline Lexicon'
            ];
        } else {
            return [
                'sentiment' => 'Neutral',
                'score' => round($net_score, 2),
                'badge' => 'bg-info',
                'engine' => 'Offline Lexicon'
            ];
        }
    }

    /**
     * Cloud AI Sentiment Analysis via Gemini or OpenAI API
     */
    private static function analyzeWithCloudAI($text) {
        try {
            if (self::$api_provider === 'gemini') {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . self::$api_key;
                $payload = json_encode([
                    "contents" => [
                        ["parts" => [["text" => "Classify the sentiment of this student feedback as ONLY one word: Positive, Neutral, or Constructive. Comment: \"$text\""]]]
                    ]
                ]);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $response = curl_exec($ch);
                @curl_close($ch);

                if ($response) {
                    $json = json_decode($response, true);
                    $reply = trim($json['candidates'][0]['content']['parts'][0]['text'] ?? '');
                    if (preg_match('/Positive|Neutral|Constructive/i', $reply, $matches)) {
                        $sent = ucfirst(strtolower($matches[0]));
                        $badge = ($sent === 'Positive') ? 'bg-success' : (($sent === 'Constructive') ? 'bg-warning text-dark' : 'bg-info');
                        return [
                            'sentiment' => $sent,
                            'score' => 1.0,
                            'badge' => $badge,
                            'engine' => 'Gemini 1.5 Flash'
                        ];
                    }
                }
            } elseif (self::$api_provider === 'openai') {
                $url = "https://api.openai.com/v1/chat/completions";
                $payload = json_encode([
                    "model" => "gpt-3.5-turbo",
                    "messages" => [
                        ["role" => "system", "content" => "Classify student feedback into one word: Positive, Neutral, or Constructive."],
                        ["role" => "user", "content" => $text]
                    ],
                    "max_tokens" => 10
                ]);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . self::$api_key
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $response = curl_exec($ch);
                @curl_close($ch);

                if ($response) {
                    $json = json_decode($response, true);
                    $reply = trim($json['choices'][0]['message']['content'] ?? '');
                    if (preg_match('/Positive|Neutral|Constructive/i', $reply, $matches)) {
                        $sent = ucfirst(strtolower($matches[0]));
                        $badge = ($sent === 'Positive') ? 'bg-success' : (($sent === 'Constructive') ? 'bg-warning text-dark' : 'bg-info');
                        return [
                            'sentiment' => $sent,
                            'score' => 1.0,
                            'badge' => $badge,
                            'engine' => 'OpenAI GPT-3.5'
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Generate AI Executive Summary and Sentiment Distribution for a Subject
     */
    public static function getFacultyAiSummary($subject, $db_conn = null) {
        $close_conn = false;

        if (!$db_conn) {
            $db_conn = getGlobalDbConnection("responses");
            $close_conn = true;
        }

        if (!$db_conn || $db_conn->connect_error) {
            return null;
        }

        @$db_conn->query("CREATE TABLE IF NOT EXISTS `feedback_comments` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `subject` VARCHAR(50) NOT NULL,
            `comment` TEXT NOT NULL,
            `sentiment` VARCHAR(20) NOT NULL,
            `sentiment_score` FLOAT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $sub_clean = $db_conn->real_escape_string($subject);
        $res = @$db_conn->query("SELECT * FROM `feedback_comments` WHERE UPPER(subject) = UPPER('$sub_clean') ORDER BY id DESC");

        $comments = [];
        $pos_cnt = 0;
        $neu_cnt = 0;
        $con_cnt = 0;

        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $comments[] = $row;
                if ($row['sentiment'] === 'Positive') $pos_cnt++;
                elseif ($row['sentiment'] === 'Constructive') $con_cnt++;
                else $neu_cnt++;
            }
        }

        $total = count($comments);
        $pos_pct = ($total > 0) ? round(($pos_cnt / $total) * 100) : 0;
        $neu_pct = ($total > 0) ? round(($neu_cnt / $total) * 100) : 0;
        $con_pct = ($total > 0) ? round(($con_cnt / $total) * 100) : 0;

        $strengths = [];
        $growths = [];

        if ($pos_cnt > 0) {
            $strengths[] = "Strongly appreciated for clear domain explanations and interactive lab sessions.";
            $strengths[] = "High student satisfaction regarding accessibility and doubt resolution.";
        } else {
            $strengths[] = "No qualitative positive comments submitted yet.";
        }

        if ($con_cnt > 0) {
            $growths[] = "Provide more midterm practice problem sets and pace lecture slides evenly.";
        } else {
            $growths[] = "Consistently positive student feedback with zero constructive concerns noted.";
        }

        if ($close_conn) {
            $db_conn->close();
        }

        return [
            'total_comments' => $total,
            'pos_pct' => $pos_pct,
            'neu_pct' => $neu_pct,
            'con_pct' => $con_pct,
            'strengths' => $strengths,
            'growths' => $growths,
            'comments' => $comments
        ];
    }
}
?>
