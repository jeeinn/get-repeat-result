<?php
/**
 * Created by PhpStorm.
 * User: xyw
 * Date: 2017/5/4
 * Time: 下午12:20
 */

/**
 * Class tools
 * 工具类
 */
class Tools
{
    public function out_put($str)
    {
        fwrite(STDOUT,$str.PHP_EOL);
    }

    public function out_put_exit($str)
    {
        exit($str.PHP_EOL);
    }

    /**
     * @return float
     * 获取以 Mb 为单位的内存使用
     */
    public function memory_used()
    {
        return round(memory_get_usage()/(1024*1024),4);
    }
}

/**
 * Class runtime
 * 单位 ms 毫秒
 */
class Runtime
{
    private $begin_time = 0;
    private $end_time = 0;

    public function get_microtime()
    {
        return microtime(true);
    }
    public function begin()
    {
        $this->begin_time = $this->get_microtime();
    }
    public function end()
    {
        $this->end_time = $this->get_microtime();
    }
    public function used()
    {
        return round(($this->end_time-$this->begin_time)*1000,4);
    }
}

/**
 * Class Deal
 * 处理文件
 */
class Deal
{
    private $tools = null;
    private $file_name = '';
    private $res_file_name = '';
    private $lines = array();
    private $path_parts = array();
    private $fh_result = false;
    private $raw_number = 0;
    private $runtime = null;
    private $repeat_number = 0;
    private $unique_number = 0;

    public function __construct($argc,$argv)
    {
        set_time_limit(0);
        date_default_timezone_set('Asia/Shanghai');
        $this->tools = new Tools();

        if (PHP_SAPI != "cli") $this->tools->out_put_exit('Need cli Mode');
        if($argc<2) $this->tools->out_put_exit('Param missing.');
        $this->file_name = $argv[1];
        if(!file_exists($this->file_name)) $this->tools->out_put_exit('File does not exist.');
        $this->lines = file($this->file_name) or $this->tools->out_put_exit('File read fail, maybe permission denied.');
        $this->path_parts = pathinfo($this->file_name);
        $this->res_file_name = rtrim($this->path_parts['dirname'],'.').$this->path_parts['filename'].'_repeat_'.date('YmdHis',time()).'.'.$this->path_parts['extension'];
        $this->fh_result = fopen($this->res_file_name,'a+');
        if(!$this->fh_result) $this->tools->out_put_exit('Write repeat result fail, maybe permission denied.');

        $this->raw_number = count($this->lines);
        $this->runtime = new Runtime();
    }

    public function get_result(array $lines)
    {
        $result = array();
        foreach ($lines as $out){
            $tmp_count = 0;
            foreach ($lines as $in){
                if($in === $out){
                    ++$tmp_count;
                    if($tmp_count>1){
                        $result[$in] = $tmp_count;
                    }
                }
            }
        }
        return $result;
    }

    public function write_result($result)
    {
        fwrite($this->fh_result,'source_data,repeat_times'.PHP_EOL);
        foreach ($result as $key => $value) {
            fwrite($this->fh_result,trim($key).','.trim($value).PHP_EOL);
        }
    }

    public function append_result()
    {
        $write_str = 'Row number:'.$this->raw_number.PHP_EOL;
        $write_str .= 'Repeat number:'.$this->repeat_number.PHP_EOL;
        $write_str .= 'Unique number:'.$this->unique_number.PHP_EOL;
        $write_str .= 'Time used:'.$this->runtime->used().'ms'.PHP_EOL;
        $write_str .= 'Memory used:'.$this->tools->memory_used().'Mb'.PHP_EOL;
        fwrite($this->fh_result,PHP_EOL.$write_str);
        return $write_str;
    }

    public function start()
    {
        $this->tools->out_put('Starting...');
        sleep(1);
        $this->runtime->begin();

        $result = $this->get_result($this->lines);
        $this->write_result($result);

        $this->runtime->end();
        $this->tools->out_put('Down !');

        $this->repeat_number = count($result);
        $this->unique_number = $this->raw_number-$this->repeat_number;
        $write_str = $this->append_result();
        $this->tools->out_put_exit($write_str);
        fclose($this->fh_result);
    }
}

$deal = new Deal($argc,$argv);
$deal->start();
