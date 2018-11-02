<?php

namespace App\Extensions;

/**
 * 数据表备份
 */
class Dbbak
{

    /**
     * 数据库主机
     * @var string
     */
    public $dbhost;

    /**
     * 数据用户名
     * @var string
     */
    public $dbuser;

    /**
     * 数据库密码
     * @var string
     */
    public $dbpw;

    /**
     * 数据库名称
     * @var string
     */
    public $dbname;

    /**
     * 备份目录
     * @var string
     */
    public $dataDir;

    /**
     * 临时存放SQL
     * @var string
     */
    protected $transfer = "";

    /**
     * 初始化
     * @param string $dbhost  主机名
     * @param string $dbuser  用户名
     * @param string $dbpw    密码
     * @param string $dbname  数据库名
     * @param string $charset 编码
     * @param string $dir     备份目录
     */
    public function __construct($dbhost, $dbuser, $dbpw, $dbname, $charset='utf8', $dir='data/dbbak/')
    {
        $this->connect($dbhost, $dbuser, $dbpw, $dbname, $charset);//连接数据
        $this->dataDir=$dir;
    }

    /**
     * 数据库连接
     * @param string $host    数据库主机名
     * @param string $user    用户名
     * @param string $pwd     密码
     * @param string $db      选择数据库名
     * @param string $charset 编码方式
     */
    public function connect($dbhost, $dbuser, $dbpw, $dbname, $charset='utf8')
    {
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpw = $dbpw;
        $this->dbname = $dbname;
        if (!$conn = mysql_connect($dbhost, $dbuser, $dbpw)) {
            $this->error('无法连接数据库服务器');
            return false;
        }
        mysql_select_db($this->dbname) or $this->error('选择数据库失败');
        mysql_query("set names $charset");
        return true;
    }

    /**
     * 列表数据库中的表
     * @param  database $database 要操作的数据库名
     * @return array
     */
    public function getTables($database='')
    {
        $database=empty($database)?$this->dbname:$database;
        $result=mysql_query("SHOW TABLES FROM `$database`") or die(mysql_error());
        //	$result = mysql_list_tables($database);//mysql_list_tables函数不建议使用
        while ($tmpArry = mysql_fetch_row($result)) {
            $dbArry[]  = $tmpArry[0];
        }
        return $dbArry;
    }

    /**
     * 生成sql文件，导出数据库
     * @param string $sql sql    语句
     * @param number $subsection 分卷大小，以KB为单位，为0表示不分卷
     * @return boolean
     */
    public function exportSql($table='', $subsection=0)
    {
        $table=empty($table)?$this->getTables():$table;
        if (!$this->_checkDir($this->dataDir)) {
            $this->error('您没有权限操作目录,备份失败');
            return false;
        }
        
        if ($subsection == 0) {
            if (!is_array($table)) {
                $this->_setSql($table, 0, $this->transfer);
            } else {
                for ($i=0;$i<count($table);$i++) {
                    $this->_setSql($table[$i], 0, $this->transfer);
                }
            }
            $fileName = $this->dataDir.date("Ymd", time()).'_all.sql.php';
            if (!$this->_writeSql($fileName, $this->transfer)) {
                return false;
            }
        } else {
            if (!is_array($table)) {
                $sqlArry = $this->_setSql($table, $subsection, $this->transfer);
                $sqlArry[] = $this->transfer;
            } else {
                $sqlArry = [];
                for ($i=0;$i<count($table);$i++) {
                    $tmpArry = $this->_setSql($table[$i], $subsection, $this->transfer);
                    $sqlArry = array_merge($sqlArry, $tmpArry);
                }
                $sqlArry[] = $this->transfer;
            }
            for ($i=0;$i<count($sqlArry);$i++) {
                $fileName = $this->dataDir.date("Ymd", time()).'_part'.$i.'.sql.php';
                if (!$this->_writeSql($fileName, $sqlArry[$i])) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * 载入sql文件，恢复数据库
     * @param diretory $dir
     * @return boolean
     */
    public function importSql($dir='')
    {
        if (is_file($dir)) {
            return $this->_importSqlFile($dir);
        }
        $dir=empty($dir)?$this->dataDir:$dir;
        if ($link = opendir($dir)) {
            $fileArry = scandir($dir);
            $pattern = "/_part[0-9]+.sql.php$|_all.sql.php$/";
            $num=count($fileArry);
            for ($i=0;$i<$num;$i++) {
                if (preg_match($pattern, $fileArry[$i])) {
                    if (false==$this->_importSqlFile($dir.$fileArry[$i])) {
                        return false;
                    }
                }
            }
            return true;
        }
    }
    
    /**
     * 执行数据库恢复
     * @param  string $filename 文件名
     * @return boolean
     */
    protected function _importSqlFile($filename='')
    {
        $sqls=file_get_contents($filename);
        $sqls=substr($sqls, 13);
        $sqls=explode("\n", $sqls);
        if (empty($sqls)) {
            return false;
        }
            
        foreach ($sqls as $sql) {
            if (empty($sql)) {
                continue;
            }
            if (!mysql_query(trim($sql))) {
                $this->error('恢复失败：'.mysql_error());
                return false;
            }
        }
        return true;
    }
    
    /**
     * 生成sql语句
     * @param   string $table 要备份的表
     * @return  array
     */
    protected function _setSql($table, $subsection=0, &$tableDom='')
    {
        $tableDom .= "DROP TABLE IF EXISTS $table\n";
        $createtable = mysql_query("SHOW CREATE TABLE $table");
        $create = mysql_fetch_row($createtable);
        $create[1] = str_replace("\n", "", $create[1]);
        $create[1] = str_replace("\t", "", $create[1]);

        $tableDom  .= $create[1].";\n";

        $rows = mysql_query("SELECT * FROM $table");
        $numfields = mysql_num_fields($rows);
        $numrows = mysql_num_rows($rows);
        $n = 1;
        $sqlArry = [];
        while ($row = mysql_fetch_row($rows)) {
            $comma = "";
            $tableDom  .= "INSERT INTO $table VALUES(";
            for ($i = 0; $i < $numfields; $i++) {
                $tableDom  .= $comma."'".mysql_escape_string($row[$i])."'";
                $comma = ",";
            }
            $tableDom  .= ")\n";
            if ($subsection != 0 && strlen($this->transfer)>=$subsection*1000) {
                $sqlArry[$n]= $tableDom;
                $tableDom = '';
                $n++;
            }
        }
        return $sqlArry;
    }
   
    /**
     * 验证目录是否有效，同时删除该目录下的所有文件
     * @param string $dir
     * @return boolean
     */
    protected function _checkDir($dir)
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777);
        }
        if (is_dir($dir)) {
            if ($link = opendir($dir)) {
                $fileArry = scandir($dir);
                for ($i=0;$i<count($fileArry);$i++) {
                    if ($fileArry[$i]!='.' || $fileArry[$i] != '..') {
                        @unlink($dir.$fileArry[$i]);
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * 将数据写入到文件中
     * @param file   $fileName 文件名
     * @param string $str       要写入的信息
     * @return boolean
     */
    protected function _writeSql($fileName, $str)
    {
        $re = true;
        if (!$fp=@fopen($fileName, "w+")) {
            $re=false;
            $this->error("在打开文件时遇到错误，备份失败!");
        }
        if (!@fwrite($fp, '<?php exit;?>'.$str)) {
            $re=false;
            $this->error("在写入信息时遇到错误，备份失败!");
        }
        if (!@fclose($fp)) {
            $re=false;
            $this->error("在关闭文件 时遇到错误，备份失败!");
        }
        return $re;
    }

    /**
     * 错误输出
     * @param  string $str 错误信息
     * @return void
     */
    public function error($str)
    {
        throw new \Exception($str, 500);
    }
}
