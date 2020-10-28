<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;

//use SplStack;

class AlarmTemplateFilter
{
//     Hyperf BASE_PATH
    private const SENSTIVE_WORDS_PATH = BASE_PATH . '/resource/senstiveWordsFile.txt';

    private $senstiveTree;

//    敏感词文本路径 测试用
//    private const SENSTIVE_WORDS_PATH = '../../resource/senstiveWordsFile.txt';

    public function __construct()
    {
        $this->buildSenstiveTree(AlarmTemplateFilter::SENSTIVE_WORDS_PATH);
    }

    private function __clone()
    {
    }

    /**
     * 短信通知渠道模板过滤器.
     *
     * @param string $sourceContent 用户编辑的模板源串
     * @param array $symbol 需过滤的符号
     * @return array
     */
    public function symbolFilter($sourceContent, $symbol)
    {
        $res = [];
        $wordArr = $this->splitStr($sourceContent);
        foreach ($wordArr as $item) {
            if (in_array($item, $symbol)) {
                $res[] = $item;
            }
        }

        return $res;
    }

    /**
     * 返回用户源串中的敏感词数组.
     *
     * @param string $sourceContent 用户编辑的模板源串
     * @return array
     */
    public function senstiveFilte(&$sourceContent)
    {
        $i = 0;
        $res = [];
        $wordArr = $this->splitStr($sourceContent);

        while ($i < count($wordArr)) {
            if ($wordArr[$i] == '_' || ! isset($this->senstiveTree[$wordArr[$i]])) {
                $i++;
                continue;
            }

            $curNode = &$this->senstiveTree[$wordArr[$i]];

            $curIndex = $i;
            while (++$i < count($wordArr)) {
                if (! isset($curNode[$wordArr[$i]])) {
                    break;
                }

                $curNode = &$curNode[$wordArr[$i]];

                if (isset($curNode['end'])) {
                    $curWords = '';
                    while ($curIndex <= $i) {
                        $curWords .= $wordArr[$curIndex++];
                    }
                    $res[] = $curWords;
                    break;
                }
            }
        }
        return $res;
    }

    /**
     * 构建DFA有限状态机字典树.
     * @param mixed $senstiveWordsFilePath
     */
    private function buildSenstiveTree($senstiveWordsFilePath)
    {
        $allSenstiveWords = self::readFile($senstiveWordsFilePath);
        foreach ($allSenstiveWords as $words) {
            $wordArr = $this->splitStr($words);
            $curNode = &$this->senstiveTree;
            foreach ($wordArr as $char) {
                if (! isset($curNode)) {
                    $curNode[$char] = [];
                }
                $curNode = &$curNode[$char];
            }
            $curNode['end'] = true;
        }
    }

    /**
     * 将句子或单词拆分成Uniocode字符数组.
     * @param mixed $words
     */
    private function splitStr($words)
    {
        return preg_split('//u', $words, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 读敏感词文本文件.
     * @param mixed $file_path
     */
    private static function readFile($file_path)
    {
        if (! file_exists($file_path)) {
            throw new AppException('敏感词源文件不存在，请立即联系系统管理员!');
        }
        $handle = fopen($file_path, 'r');
        while (! feof($handle)) {
            yield trim(fgets($handle));
        }
        fclose($handle);
    }

    /*
     * '{}'括号匹配
     *
     *
     */
//    public function validBrace(&$wordArr)
//    {
//        $stack = new SplStack();
//        foreach ($wordArr as &$item) {
//            if ($stack->isEmpty() && $item == '}') {
//                return false;
//            }
//            if ($item == '{') {
//                $stack->push($item);
//            } elseif ($item == '}') {
//                if (!$stack->isEmpty() && $stack->top() == '{') {
//                    $stack->pop();
//                } else {
//                    return false;
//                }
//            } else {
//                continue;
//            }
//        }
//        return $stack->isEmpty();
//    }

    /*
     * 过滤用户输入文本并返回敏感词数组
     * 此项目当前未用此方法，请按需修改
     * @param string $sourceContent 用户编辑的模板源串
     * @param int    $skiDist 严格度：检测时允许跳过的间隔
     * 只能是[0, 4]闭区间 严格度过大会导致用户体验不良
     * 例：2严格度可检测'出-0售'中'出售'敏感词
     * @return array
     */
//    public function senstiveFilter($sourceContent, $skiDist = 0)
//    {
//        $res = [];
//
//        $skiDist = max(0, $skiDist);
//        if ($skiDist > 4) {
//            $skiDist = 4;
//        }
//
//        $wordArr = $this->splitStr($sourceContent);
//
//        for ($i = 0; $i < count($wordArr); $i++) {
//            $word = $wordArr[$i];
//            if (!isset($this->senstiveTree[$word])) {
//                continue;
//            }
//            $curNode = &$this->senstiveTree[$word];
//            $dist = 0;
//            $matchIndex = [$i];
//
//            for ($j = $i + 1; $j < count($wordArr) && $dist < $skiDist; $j++) {
//                if (!isset($curNode[$wordArr[$j]])) {
//                    $dist ++;
//                    continue;
//                }
//                $dist = 0;
//                $matchIndex[] = $j;
//                $curNode = &$curNode[$wordArr[$j]];
//
//                if (isset($curNode['end'])) {
//                    $curWords = '';
//                    foreach ($matchIndex as $index) {
//                        $curWords .= $wordArr[$index];
//                    }
//                    $i = max($matchIndex);
//                    $res[] = $curWords;
//                    break;
//                }
//            }
//        }
//        return $res;
//    }
}

//测试性能
//$start_memory = memory_get_usage();
//$start_time = microtime(true);
//$userContent = '用户源串你懂得敏感词 出   售 2 杂 志毛22泽22东出售';
//$filter = new AlarmTemplateFilter();
//$res = $filter->senstiveFilte($userContent);
//print_r($res);
//$end_time = microtime(true);                        //获取程序执行结束的时间
//$run_time = ($end_time - $start_time) * 1000;       //计算差值 毫秒
//$end_memory = memory_get_usage();
//
//echo "时间：{$run_time}毫秒\n";
//echo '内存占用:' . round(($end_memory - $start_memory) / 1024 / 1024, 2) . 'MB';
