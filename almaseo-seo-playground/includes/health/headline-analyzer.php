<?php
/**
 * AlmaSEO Headline Analyzer
 *
 * Scores headlines for click-worthiness and SEO effectiveness.
 * Used both server-side (health calculation) and client-side (instant feedback).
 *
 * @package AlmaSEO
 * @subpackage Health
 * @since 8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Headline_Analyzer {

    /**
     * Power words that drive clicks.
     *
     * @var array
     */
    private static $power_words = array(
        'amazing', 'proven', 'secret', 'ultimate', 'essential', 'guaranteed',
        'powerful', 'incredible', 'exclusive', 'revolutionary', 'breakthrough',
        'critical', 'vital', 'crucial', 'insider', 'master', 'hack', 'tricks',
        'definitive', 'complete', 'comprehensive', 'absolute', 'epic', 'massive',
        'instantly', 'effortless', 'remarkable', 'extraordinary', 'stunning',
        'brilliant', 'spectacular', 'genius', 'supercharge', 'dominate',
        'skyrocket', 'explode', 'boost', 'maximize', 'transform', 'unlock',
        'free', 'new', 'now', 'easy', 'fast', 'simple', 'quick', 'save',
        'best', 'top', 'first', 'last', 'only', 'never', 'always', 'must',
    );

    /**
     * Emotional words that trigger engagement.
     *
     * @var array
     */
    private static $emotional_words = array(
        'love', 'hate', 'fear', 'joy', 'angry', 'happy', 'sad', 'excited',
        'worried', 'surprised', 'shocked', 'thrilled', 'devastated', 'furious',
        'heartbreaking', 'inspiring', 'terrifying', 'hilarious', 'outrageous',
        'beautiful', 'ugly', 'disgusting', 'wonderful', 'horrible', 'fantastic',
        'awful', 'dangerous', 'scary', 'painful', 'embarrassing', 'awkward',
        'crazy', 'insane', 'unbelievable', 'ridiculous', 'strange', 'weird',
        'controversial', 'alarming', 'disturbing', 'touching', 'heartwarming',
    );

    /**
     * Common filler words.
     *
     * @var array
     */
    private static $common_words = array(
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
        'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
        'could', 'should', 'may', 'might', 'can', 'shall', 'it', 'its', 'this',
        'that', 'these', 'those', 'i', 'you', 'he', 'she', 'we', 'they', 'me',
        'my', 'your', 'his', 'her', 'our', 'their', 'what', 'which', 'who',
        'when', 'where', 'how', 'why', 'not', 'no', 'so', 'if', 'then', 'than',
        'too', 'very', 'just', 'about', 'up', 'out', 'all', 'also', 'as',
    );

    /**
     * Analyze a headline and return a score with breakdown.
     *
     * @param string $headline The headline to analyze.
     * @return array { score: int (0-100), checks: array }
     */
    public static function analyze( $headline ) {
        $headline = trim( $headline );
        if ( empty( $headline ) ) {
            return array( 'score' => 0, 'checks' => array() );
        }

        $checks = array();
        $score  = 0;
        $words  = preg_split( '/\s+/', strtolower( $headline ) );
        $word_count = count( $words );

        // 1. Word count: 6–13 ideal (20 pts)
        $wc_pass = $word_count >= 6 && $word_count <= 13;
        $checks['word_count'] = array(
            'label' => __( 'Word Count', 'almaseo' ),
            'value' => $word_count,
            'pass'  => $wc_pass,
            'tip'   => $wc_pass
                ? sprintf( __( '%d words — ideal range.', 'almaseo' ), $word_count )
                : sprintf( __( '%d words — aim for 6–13 words.', 'almaseo' ), $word_count ),
        );
        if ( $wc_pass ) {
            $score += 20;
        }

        // 2. Character length: 50–60 ideal (15 pts)
        $char_count = mb_strlen( $headline );
        $cl_pass = $char_count >= 50 && $char_count <= 60;
        $checks['char_length'] = array(
            'label' => __( 'Character Length', 'almaseo' ),
            'value' => $char_count,
            'pass'  => $cl_pass,
            'tip'   => $cl_pass
                ? sprintf( __( '%d chars — fits Google\'s title display.', 'almaseo' ), $char_count )
                : sprintf( __( '%d chars — aim for 50–60 characters.', 'almaseo' ), $char_count ),
        );
        if ( $cl_pass ) {
            $score += 15;
        }

        // 3. Power words (15 pts)
        $power_found = self::count_word_matches( $words, self::$power_words );
        $pw_pass = $power_found > 0;
        $checks['power_words'] = array(
            'label' => __( 'Power Words', 'almaseo' ),
            'value' => $power_found,
            'pass'  => $pw_pass,
            'tip'   => $pw_pass
                ? sprintf( __( '%d power word(s) found — great for driving clicks.', 'almaseo' ), $power_found )
                : __( 'Add a power word (e.g., "proven", "essential", "ultimate").', 'almaseo' ),
        );
        if ( $pw_pass ) {
            $score += 15;
        }

        // 4. Emotional words (15 pts)
        $emotional_found = self::count_word_matches( $words, self::$emotional_words );
        $ew_pass = $emotional_found > 0;
        $checks['emotional_words'] = array(
            'label' => __( 'Emotional Words', 'almaseo' ),
            'value' => $emotional_found,
            'pass'  => $ew_pass,
            'tip'   => $ew_pass
                ? sprintf( __( '%d emotional word(s) — helps engage readers.', 'almaseo' ), $emotional_found )
                : __( 'Consider adding an emotional trigger (e.g., "surprising", "inspiring").', 'almaseo' ),
        );
        if ( $ew_pass ) {
            $score += 15;
        }

        // 5. Contains a number (10 pts)
        $has_number = (bool) preg_match( '/\d/', $headline );
        $checks['has_number'] = array(
            'label' => __( 'Contains Number', 'almaseo' ),
            'value' => $has_number ? 1 : 0,
            'pass'  => $has_number,
            'tip'   => $has_number
                ? __( 'Numbers attract attention in search results.', 'almaseo' )
                : __( 'Headlines with numbers get 36% more clicks.', 'almaseo' ),
        );
        if ( $has_number ) {
            $score += 10;
        }

        // 6. Question format (10 pts)
        $is_question = (bool) preg_match( '/\?$/', trim( $headline ) )
            || (bool) preg_match( '/^(how|what|why|when|where|who|which|can|do|does|is|are|will|should)\b/i', $headline );
        $checks['is_question'] = array(
            'label' => __( 'Question Format', 'almaseo' ),
            'value' => $is_question ? 1 : 0,
            'pass'  => $is_question,
            'tip'   => $is_question
                ? __( 'Question headlines spark curiosity.', 'almaseo' )
                : __( 'Try phrasing as a question for higher engagement.', 'almaseo' ),
        );
        if ( $is_question ) {
            $score += 10;
        }

        // 7. Word balance: common/uncommon ratio (15 pts)
        // Good headlines mix common words (~20-30%) with uncommon/specific words
        $common_count = self::count_word_matches( $words, self::$common_words );
        $common_pct = $word_count > 0 ? ( $common_count / $word_count ) * 100 : 0;
        $balance_pass = $common_pct >= 15 && $common_pct <= 50;
        $checks['word_balance'] = array(
            'label' => __( 'Word Balance', 'almaseo' ),
            'value' => round( $common_pct ),
            'pass'  => $balance_pass,
            'tip'   => $balance_pass
                ? sprintf( __( '%d%% common words — good balance of familiar and specific.', 'almaseo' ), round( $common_pct ) )
                : ( $common_pct > 50
                    ? __( 'Too many generic words — add more specific, descriptive terms.', 'almaseo' )
                    : __( 'Add some common connecting words for natural readability.', 'almaseo' ) ),
        );
        if ( $balance_pass ) {
            $score += 15;
        }

        return array(
            'score'  => min( 100, $score ),
            'checks' => $checks,
        );
    }

    /**
     * Count how many words in the headline match a word list.
     *
     * @param array $headline_words Lowercased words from headline.
     * @param array $word_list      Reference word list.
     * @return int
     */
    private static function count_word_matches( $headline_words, $word_list ) {
        $count = 0;
        $lookup = array_flip( $word_list );
        foreach ( $headline_words as $word ) {
            $clean = preg_replace( '/[^a-z]/', '', $word );
            if ( isset( $lookup[ $clean ] ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get word lists for client-side JS scoring.
     *
     * @return array
     */
    public static function get_word_lists() {
        return array(
            'power'    => self::$power_words,
            'emotional' => self::$emotional_words,
            'common'   => self::$common_words,
        );
    }
}
