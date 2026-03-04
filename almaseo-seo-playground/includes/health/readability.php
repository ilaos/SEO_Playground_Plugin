<?php
/**
 * AlmaSEO Enhanced Readability Analyzer
 *
 * Comprehensive readability analysis: Flesch Reading Ease, passive voice,
 * transition words, consecutive sentence starts, subheading distribution.
 *
 * @package AlmaSEO
 * @subpackage Health
 * @since 8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Readability_Analyzer {

    /**
     * Transition words / phrases for English.
     *
     * @var array
     */
    private static $transition_words = array(
        // Addition
        'additionally', 'also', 'besides', 'furthermore', 'moreover', 'in addition',
        'likewise', 'similarly', 'as well as', 'not only', 'coupled with',
        // Contrast
        'however', 'nevertheless', 'nonetheless', 'although', 'whereas', 'despite',
        'on the other hand', 'in contrast', 'conversely', 'instead', 'rather',
        'yet', 'still', 'even so', 'on the contrary',
        // Cause / Effect
        'therefore', 'consequently', 'thus', 'hence', 'accordingly', 'as a result',
        'because', 'since', 'due to', 'owing to', 'for this reason',
        // Sequence
        'firstly', 'secondly', 'thirdly', 'finally', 'meanwhile', 'subsequently',
        'next', 'then', 'afterward', 'previously', 'in the first place',
        'to begin with', 'in conclusion', 'ultimately', 'lastly',
        // Example
        'for example', 'for instance', 'such as', 'specifically', 'in particular',
        'namely', 'to illustrate', 'in other words',
        // Emphasis
        'indeed', 'certainly', 'undoubtedly', 'clearly', 'obviously',
        'in fact', 'above all', 'most importantly',
        // Summary
        'in summary', 'to summarize', 'in short', 'overall', 'altogether',
        'in brief', 'to conclude', 'all in all',
    );

    /**
     * Passive voice indicator patterns (English).
     *
     * @var array
     */
    private static $passive_auxiliaries = array(
        'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'get', 'gets', 'got', 'gotten', 'getting',
    );

    /**
     * Run full readability analysis on content.
     *
     * @param string $text     Plain text content (HTML stripped).
     * @param string $html     Original HTML content (for subheading analysis).
     * @return array
     */
    public static function analyze( $text, $html = '' ) {
        $results = array();

        if ( empty( trim( $text ) ) ) {
            return array(
                'overall_pass' => false,
                'checks'       => array(),
            );
        }

        $sentences = self::split_sentences( $text );
        $words     = preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );
        $word_count = count( $words );

        // 1. Flesch Reading Ease
        $results['flesch'] = self::flesch_reading_ease( $text, $sentences, $words, $word_count );

        // 2. Sentence length
        $results['sentence_length'] = self::sentence_length_check( $sentences );

        // 3. Passive voice
        $results['passive_voice'] = self::passive_voice_check( $sentences );

        // 4. Transition words
        $results['transition_words'] = self::transition_word_check( $sentences, $text );

        // 5. Consecutive sentence starts
        $results['consecutive_starts'] = self::consecutive_starts_check( $sentences );

        // 6. Subheading distribution
        $results['subheading_distribution'] = self::subheading_distribution_check( $html ?: $text, $word_count );

        // Overall: pass if ≥4 of 6 checks pass
        $pass_count = 0;
        foreach ( $results as $check ) {
            if ( ! empty( $check['pass'] ) ) {
                $pass_count++;
            }
        }

        return array(
            'overall_pass' => $pass_count >= 4,
            'pass_count'   => $pass_count,
            'total_checks' => count( $results ),
            'checks'       => $results,
        );
    }

    /**
     * Flesch Reading Ease score.
     * 90-100 Very Easy, 60-70 Standard, 0-30 Very Difficult.
     */
    private static function flesch_reading_ease( $text, $sentences, $words, $word_count ) {
        $sentence_count = count( $sentences );
        if ( $sentence_count === 0 || $word_count === 0 ) {
            return array(
                'pass'  => false,
                'label' => __( 'Flesch Reading Ease', 'almaseo' ),
                'value' => 0,
                'tip'   => __( 'Not enough content to calculate.', 'almaseo' ),
            );
        }

        $syllables = self::count_syllables( $text );
        $score = 206.835 - ( 1.015 * ( $word_count / $sentence_count ) ) - ( 84.6 * ( $syllables / $word_count ) );
        $score = max( 0, min( 100, round( $score, 1 ) ) );

        $pass = $score >= 60;
        if ( $score >= 80 ) {
            $label_text = __( 'Easy to read', 'almaseo' );
        } elseif ( $score >= 60 ) {
            $label_text = __( 'Fairly easy', 'almaseo' );
        } elseif ( $score >= 40 ) {
            $label_text = __( 'Somewhat difficult', 'almaseo' );
        } else {
            $label_text = __( 'Difficult to read', 'almaseo' );
        }

        return array(
            'pass'  => $pass,
            'label' => __( 'Flesch Reading Ease', 'almaseo' ),
            'value' => $score,
            'tip'   => sprintf( '%s (%s). %s', $score, $label_text,
                $pass ? __( 'Great readability!', 'almaseo' ) : __( 'Aim for 60+ by using shorter words and sentences.', 'almaseo' ) ),
        );
    }

    /**
     * Check average sentence length.
     * Ideal: ≤20 words per sentence on average.
     */
    private static function sentence_length_check( $sentences ) {
        if ( empty( $sentences ) ) {
            return array( 'pass' => true, 'label' => __( 'Sentence Length', 'almaseo' ), 'value' => 0, 'tip' => __( 'No sentences found.', 'almaseo' ) );
        }

        $long_count = 0;
        foreach ( $sentences as $s ) {
            $wc = str_word_count( trim( $s ) );
            if ( $wc > 20 ) {
                $long_count++;
            }
        }

        $pct = ( $long_count / count( $sentences ) ) * 100;
        $pass = $pct <= 25; // ≤25% of sentences are long

        return array(
            'pass'  => $pass,
            'label' => __( 'Sentence Length', 'almaseo' ),
            'value' => round( $pct ),
            'tip'   => $pass
                ? sprintf( __( '%d%% of sentences are over 20 words — good.', 'almaseo' ), round( $pct ) )
                : sprintf( __( '%d%% of sentences exceed 20 words — try to keep under 25%%.', 'almaseo' ), round( $pct ) ),
        );
    }

    /**
     * Check passive voice percentage.
     * Ideal: ≤10% of sentences use passive voice.
     */
    private static function passive_voice_check( $sentences ) {
        if ( empty( $sentences ) ) {
            return array( 'pass' => true, 'label' => __( 'Passive Voice', 'almaseo' ), 'value' => 0, 'tip' => __( 'No sentences found.', 'almaseo' ) );
        }

        $passive_count = 0;
        $auxiliaries = implode( '|', self::$passive_auxiliaries );
        // Pattern: auxiliary + optional adverb + past participle (word ending in -ed/-en/-t/-n)
        $pattern = '/\b(' . $auxiliaries . ')\s+(\w+\s+)?((\w+ed|\w+en|\w+wn|\w+ght|\w+nt)\b)/i';

        foreach ( $sentences as $s ) {
            if ( preg_match( $pattern, $s ) ) {
                $passive_count++;
            }
        }

        $pct  = ( $passive_count / count( $sentences ) ) * 100;
        $pass = $pct <= 10;

        return array(
            'pass'  => $pass,
            'label' => __( 'Passive Voice', 'almaseo' ),
            'value' => round( $pct ),
            'tip'   => $pass
                ? sprintf( __( '%d%% passive sentences — good use of active voice.', 'almaseo' ), round( $pct ) )
                : sprintf( __( '%d%% passive sentences — aim for ≤10%%. Rewrite with active verbs.', 'almaseo' ), round( $pct ) ),
        );
    }

    /**
     * Check transition word usage.
     * Ideal: ≥30% of sentences contain a transition word.
     */
    private static function transition_word_check( $sentences, $text ) {
        if ( empty( $sentences ) ) {
            return array( 'pass' => true, 'label' => __( 'Transition Words', 'almaseo' ), 'value' => 0, 'tip' => __( 'No sentences found.', 'almaseo' ) );
        }

        $with_transition = 0;
        $lower_text = strtolower( $text );

        foreach ( $sentences as $s ) {
            $lower_s = strtolower( trim( $s ) );
            foreach ( self::$transition_words as $tw ) {
                if ( strpos( $lower_s, $tw ) !== false ) {
                    $with_transition++;
                    break; // Count each sentence only once
                }
            }
        }

        $pct  = ( $with_transition / count( $sentences ) ) * 100;
        $pass = $pct >= 30;

        return array(
            'pass'  => $pass,
            'label' => __( 'Transition Words', 'almaseo' ),
            'value' => round( $pct ),
            'tip'   => $pass
                ? sprintf( __( '%d%% of sentences use transitions — smooth flow.', 'almaseo' ), round( $pct ) )
                : sprintf( __( '%d%% — aim for ≥30%%. Use words like "however", "therefore", "for example".', 'almaseo' ), round( $pct ) ),
        );
    }

    /**
     * Check for consecutive sentences starting with the same word.
     * Flag if 3+ consecutive sentences start identically.
     */
    private static function consecutive_starts_check( $sentences ) {
        if ( count( $sentences ) < 3 ) {
            return array( 'pass' => true, 'label' => __( 'Sentence Variety', 'almaseo' ), 'value' => 0, 'tip' => __( 'Not enough sentences to check.', 'almaseo' ) );
        }

        $starts = array();
        foreach ( $sentences as $s ) {
            $s = trim( $s );
            if ( empty( $s ) ) {
                continue;
            }
            $first_word = strtolower( strtok( $s, " \t\n" ) );
            $starts[] = preg_replace( '/[^a-z]/', '', $first_word );
        }

        $max_consecutive = 1;
        $current_run     = 1;
        for ( $i = 1; $i < count( $starts ); $i++ ) {
            if ( $starts[ $i ] === $starts[ $i - 1 ] && ! empty( $starts[ $i ] ) ) {
                $current_run++;
                if ( $current_run > $max_consecutive ) {
                    $max_consecutive = $current_run;
                }
            } else {
                $current_run = 1;
            }
        }

        $pass = $max_consecutive < 3;

        return array(
            'pass'  => $pass,
            'label' => __( 'Sentence Variety', 'almaseo' ),
            'value' => $max_consecutive,
            'tip'   => $pass
                ? __( 'Good sentence variety — no repetitive starts.', 'almaseo' )
                : sprintf( __( '%d consecutive sentences start the same way — vary your openings.', 'almaseo' ), $max_consecutive ),
        );
    }

    /**
     * Check subheading distribution.
     * Ideal: A subheading (h2-h4) every 300 words or less.
     */
    private static function subheading_distribution_check( $html, $word_count ) {
        if ( $word_count < 300 ) {
            return array(
                'pass'  => true,
                'label' => __( 'Subheading Distribution', 'almaseo' ),
                'value' => 0,
                'tip'   => __( 'Content is short enough — subheadings not required.', 'almaseo' ),
            );
        }

        // Count subheadings (h2, h3, h4)
        $subheading_count = preg_match_all( '/<h[2-4]\b/i', $html );

        if ( $subheading_count === 0 ) {
            return array(
                'pass'  => false,
                'label' => __( 'Subheading Distribution', 'almaseo' ),
                'value' => 0,
                'tip'   => sprintf( __( '%d words with no subheadings — add h2/h3 headings to break up content.', 'almaseo' ), $word_count ),
            );
        }

        // Average words between subheadings
        $avg_between = $word_count / ( $subheading_count + 1 ); // +1 for intro before first heading
        $pass = $avg_between <= 300;

        return array(
            'pass'  => $pass,
            'label' => __( 'Subheading Distribution', 'almaseo' ),
            'value' => round( $avg_between ),
            'tip'   => $pass
                ? sprintf( __( 'Average %d words between subheadings — well-structured.', 'almaseo' ), round( $avg_between ) )
                : sprintf( __( 'Average %d words between subheadings — aim for ≤300.', 'almaseo' ), round( $avg_between ) ),
        );
    }

    /* ── Helpers ─────────────────────────────────────────────────────── */

    /**
     * Split text into sentences.
     *
     * @param string $text Plain text.
     * @return array
     */
    private static function split_sentences( $text ) {
        $sentences = preg_split( '/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
        return is_array( $sentences ) ? array_filter( $sentences, function( $s ) {
            return str_word_count( trim( $s ) ) >= 3;
        }) : array();
    }

    /**
     * Estimate syllable count for English text.
     *
     * @param string $text Plain text.
     * @return int
     */
    private static function count_syllables( $text ) {
        $words = preg_split( '/\s+/', strtolower( $text ), -1, PREG_SPLIT_NO_EMPTY );
        $total = 0;

        foreach ( $words as $word ) {
            $word = preg_replace( '/[^a-z]/', '', $word );
            $len  = strlen( $word );

            if ( $len <= 3 ) {
                $total += 1;
                continue;
            }

            // Remove trailing silent e
            $word = preg_replace( '/e$/', '', $word );

            // Count vowel groups
            preg_match_all( '/[aeiouy]+/', $word, $matches );
            $count = isset( $matches[0] ) ? count( $matches[0] ) : 1;
            $total += max( 1, $count );
        }

        return $total;
    }
}
