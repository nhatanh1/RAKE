<?php

namespace Nanhh\RAKE;

class RAKE
{
    // Build your next great package.

    /**
     * @var string $stopwords_path
     */
    public $stopwords_path;

    /**
     * @var string $stopwords_pattern
     */
    private $stopwords_pattern;

    /**
     * Build stop words pattern from file given by parameter
     *
     * @param string $stopwords_path Path to the file with stop words
     */
    function __construct($stopwords)
    {
        // $this->stopwords_path = $stopwords_path;
        $this->stopwords_pattern = $this->build_stopwords_regex($stopwords); // lấy chuỗi biểu thức chính quy từ stopwords
    }

    /**
     * Extract key phrases from input text
     *
     * @param string $text Input text
     */
    public function extract($text) // lấy cụm từ khóa từ văn bản đầu vào
    {
        $phrases_plain = self::split_sentences($text); // chia đoạn văn thành từng câu
        $phrases = $this->get_phrases($phrases_plain); // từ, cụm từ của từng câu sau khi loại bỏ khoảng trắng
        // print_r($phrases);
        $scores = $this->get_scores($phrases); // điểm của từ
        $keywords = $this->get_keywords($phrases, $scores); // tính điểm cụm từ
        arsort($keywords); // sắp xếp mảng giảm dần theo mảng

        return $keywords;
    }

    /**
     * @param string $text Text to be splitted into sentences
     */
    public static function split_sentences($text) // chia văn bản thành các câu
    {
        $content = preg_replace("/[0-9]+[.]?[0-9]*/", "|", $text);
        return preg_split("/(\.|, |,| - |\n|\(|\)|\"|[0-9]|%|\/)+/", $content); // tách chuỗi thành mảnh dùng biểu thức chính quy
    }


    /**
     * @param string $phrase Phrase to be splitted into words
     */
    public static function split_phrase($phrase) // chi cụm từ thành các từ
    {
        return explode(' ', $phrase);
    }

    /**
     * Split sentences into phrases by loaded stop words
     *
     * @param array $sentences Array of sentences
     */
    private function get_phrases($sentences) // chia các câu thành cụm từ dùng stop words
    {
        $phrases_arr = array();
        foreach ($sentences as $s) { // duyệt từng câu 
            // print_r($s); //
            $phrases_temp = preg_replace($this->stopwords_pattern, '|', $s); // gặp stop words thì thay thế bằng dấu \ (loại bỏ từ khóa khỏi câu)
            // print_r($phrases_temp);
            // echo "\n";
            $phrases = explode('|', $phrases_temp); // tách câu thành mảng phần cách bởi dấu \ lấy các cụm từ còn lại sau khi loại bỏ từ khóa. vẫn còn khoảng trắng
            foreach ($phrases as $p) { // duyệt cụm từ sau khi loại bỏ từ khóa
                $p = strtolower(trim($p));  // loại bỏ khoảng trắng
                if ($p != '') array_push($phrases_arr, $p); // nếu p là 1 từ hoặc cụm từ thêm vào mảng 
            }
        }
        // print_r($phrases_arr);
        return $phrases_arr; // trả về mảng các cụm từ sau khi loại bỏ từ khóa
    }

    /**
     * Calculate score for each word 
     *
     * @param array $phrases Array containing individual phrases
     */
    private function get_scores($phrases) // tính điểm cho mỗi từ
    {
        $frequencies = array(); // tần số xuất hiện của từ
        $degrees = array();     // tần số xuất hiện của từ

        foreach ($phrases as $p) {
            $words = self::split_phrase($p); // chia cụm từ từng câu thành các từ
            // print_r($words);
            $words_count = count($words); // số lượng từ trong 1 câu
            // var_dump($words_count);
            // echo "------------------------------\n";
            $words_degree = $words_count - 1; // mức độ các từ
            // var_dump($words_degree);
            foreach ($words as $w) { // duyệt từng từ trong câu
                $frequencies[$w] = (isset($frequencies[$w])) ? $frequencies[$w] : 0; // số lần hiện của từ. nếu chưa xuất hiện = 0 
                $frequencies[$w] += 1;  // số lần xuất lên 1
                $degrees[$w] = (isset($degrees[$w])) ? $degrees[$w] : 0; // mức độ của 1 từ, nếu chưa có thì = 0
                $degrees[$w] += $words_degree; // mức độ 1 từ + mức độ của các từ
            }
        }

        foreach ($frequencies as $word => $freq) { // duyệt mảng số lần xuất hiện của từ
            $degrees[$word] += $freq; // mức độ từ = mức độ từ + số lần xuất hiện
        }

        $scores = array();
        // print_r($frequencies);
        foreach ($frequencies as $word => $freq) { // duyệt danh sách các từ và số lần xuất hiện (word từ  freq số lần xuất hiện)
            $scores[$word] = (isset($scores[$word])) ? $scores[$word] : 0; // từ chưa có điểm => điểm = 0
            $scores[$word] = $degrees[$word] / (float) $freq; // tính điểm của từ = mức độ từ / số lần xuất hiện
        }
        // print_r($scores);
        return $scores;
    }

    /**
     * Calculate score for each phrase by words scores
     *
     * @param array $phrases Array of phrases (optimally) returned by get_phrases() method
     * @param array $scores Array of words and their scores returned by get_scores() method
     */
    private function get_keywords($phrases, $scores) // tính điểm cho cụm từ dựa theo điểm từ
    {
        $keywords = array();

        foreach ($phrases as $p) { // duyệt từng cụm từ
            $keywords[$p] = (isset($keywords[$p])) ? $keywords[$p] : 0; // cụm từ chưa có điểm = 0
            $words = self::split_phrase($p); // tách cụm từ thành từng từ
            $score = 0;

            foreach ($words as $w) { // tính tổng điểm của từng từ trong cụm từ

                $score += isset($scores[$w]) ? $scores[$w] : 0; //

                $keywords[$p] = $score; // in ra cụm từ kèm điểm
            }
        }
        // print_r($keywords);
        return $keywords;
    }


    /**
     * Get loaded stop words and return regex containing each stop word
     */
    private function build_stopwords_regex($stopwords) //BUILD stop words thành biểu thức chính quy
    {
        $stopwords_arr = $stopwords;
        $stopwords_regex_arr = array();

        foreach ($stopwords_arr as $word) {
            array_push($stopwords_regex_arr, '\b' . $word . '\b'); // thêm từng từ vào mảng  (\b.....\b  regex chuỗi không có ký tự trước, sau  
        }

        return '/\b' . implode('|', $stopwords_regex_arr) . '\b/iu'; // viết regex chứa các từ khóa /i không phân biệt chữ hoa, thường /u kiểu unicode
        // implode ('|', $stopwords_regex_arr) ghép mảng thành chuỗi cách nhau bởi dấu | => toán tử 'hoặc' trong regex
    }

    /**
     * Load stop words from an input file
     */
    private function load_stopwords() // lấy từ khóa trong file truyền vào
    {
        $stopwords = file_get_contents($this->stopwords_path); // lấy nội dung file

        $stopwords = json_decode($stopwords, true); // json_decode nội dung

        return $stopwords;
    }
}