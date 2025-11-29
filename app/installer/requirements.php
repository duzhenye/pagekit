<?php

/**
 * 表示单个PHP要求，例如已安装的扩展。
 * 它可以是强制性要求或可选建议。
 * 有一个特殊的子类，名为PhpIniRequirement，用于检查php.ini配置。
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class Requirement
{
    private $fulfilled;
    private $testMessage;
    private $helpText;
    private $helpHtml;
    private $optional;

    /**
     * 构造函数，初始化要求。
     *
     * @param Boolean     $fulfilled   是否满足要求
     * @param string      $testMessage 测试要求的消息
     * @param string      $helpHtml    解决问题的帮助文本，格式为HTML
     * @param string|null $helpText    解决问题的帮助文本（当为null时，将从$helpHtml中推断，即从HTML标签中剥离）
     * @param Boolean     $optional    是否仅为可选建议，而不是强制性要求
     */
    public function __construct($fulfilled, $testMessage, $helpHtml, $helpText = null, $optional = false)
    {
        $this->fulfilled = (Boolean) $fulfilled;
        $this->testMessage = (string) $testMessage;
        $this->helpHtml = (string) $helpHtml;
        $this->helpText = null === $helpText ? strip_tags($this->helpHtml) : (string) $helpText;
        $this->optional = (Boolean) $optional;
    }

    /**
     * 返回是否满足要求。
     *
     * @return Boolean 如果满足要求，则返回true，否则返回false
     */
    public function isFulfilled()
    {
        return $this->fulfilled;
    }

    /**
     * 返回测试要求的消息。
     *
     * @return string 测试要求的消息
     */
    public function getTestMessage()
    {
        return $this->testMessage;
    }

    /**
     * 返回解决问题的帮助文本。
     *
     * @return string 解决问题的帮助文本
     */
    public function getHelpText()
    {
        return $this->helpText;
    }

    /**
     * 返回解决问题的帮助文本，格式为HTML。
     *
     * @return string 解决问题的帮助文本，格式为HTML
     */
    public function getHelpHtml()
    {
        return $this->helpHtml;
    }

    /**
     * 返回是否仅为可选建议，而不是强制性要求。
     *
     * @return Boolean 如果仅为可选建议，则返回true，否则返回false
     */
    public function isOptional()
    {
        return $this->optional;
    }
}

/**
 * 表示PHP要求，例如php.ini配置。
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class PhpIniRequirement extends Requirement
{
    /**
     * 构造函数，初始化要求。
     *
     * @param string           $cfgName    用于ini_get()的配置名称
     * @param Boolean|callback $evaluation 一个Boolean值，指示配置是否应评估为true或false，
     *                                     或接收配置值作为参数以确定要求满足情况的回调函数
     * @param Boolean $approveCfgAbsence 如果为true，则要求将被满足，即使配置选项不存在，即ini_get()返回false。
     *                                    这对于稍后的PHP版本中已弃用的配置或可选扩展的配置（如Suhosin）很有帮助。
     *                                    例如，您需要一个配置为true，但PHP稍后删除了该配置并将其内部默认设置为true。
     * @param string|null $testMessage 测试要求的消息（当为null且$evaluation为Boolean时，将从$cfgName和$evaluation派生默认消息）
     * @param string|null $helpHtml    解决问题的帮助文本，格式为HTML（当为null且$evaluation为Boolean时，将从$cfgName和$evaluation派生默认帮助）
     * @param string|null $helpText    解决问题的帮助文本（当为null时，将从$helpHtml中推断，即从HTML标签中剥离）
     * @param Boolean     $optional    是否仅为可选建议，而不是强制性要求
     */
    public function __construct($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null, $optional = false)
    {
        $cfgValue = ini_get($cfgName);

        if (is_callable($evaluation)) {
            if (null === $testMessage || null === $helpHtml) {
                throw new InvalidArgumentException('您必须为回调评估提供参数testMessage和helpHtml。');
            }

            $fulfilled = call_user_func($evaluation, $cfgValue);
        } else {
            if (null === $testMessage) {
                $testMessage = sprintf('%s %s be %s in php.ini',
                    $cfgName,
                    $optional ? 'should' : 'must',
                    $evaluation ? 'enabled' : 'disabled'
                );
            }

            if (null === $helpHtml) {
                $helpHtml = sprintf('在php.ini中设置<strong>%s</strong>为<strong>%s</strong><a href="#phpini">*</a>。',
                    $cfgName,
                    $evaluation ? 'on' : 'off'
                );
            }

            $fulfilled = $evaluation == $cfgValue;
        }

        parent::__construct($fulfilled || ($approveCfgAbsence && false === $cfgValue), $testMessage, $helpHtml, $helpText, $optional);
    }
}

/**
 * 表示要求集合，例如一组Requirement实例。
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class RequirementCollection implements IteratorAggregate
{
    private $requirements = array();

    /**
     * 获取当前RequirementCollection作为Iterator。
     *
     * @return Traversable 一个Traversable接口
     */
    public function getIterator()
    {
        return new ArrayIterator($this->requirements);
    }

    /**
     * 添加一个要求。
     *
     * @param Requirement $requirement 一个Requirement实例
     */
    public function add(Requirement $requirement)
    {
        $this->requirements[] = $requirement;
    }

    /**
     * 添加一个强制性要求。
     *
     * @param Boolean     $fulfilled   是否满足要求
     * @param string      $testMessage 测试要求的消息（当为null且$evaluation为布尔值时，将派生默认消息）
     * @param string      $helpHtml    解决问题的HTML格式帮助文本（当为null且$evaluation为布尔值时，将派生默认帮助）
     * @param string|null $helpText    解决问题的帮助文本（当为null时，将从$helpHtml中推断，即从HTML标签中剥离）
     */
    public function addRequirement($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        $this->add(new Requirement($fulfilled, $testMessage, $helpHtml, $helpText, false));
    }

    /**
     * 添加一个可选建议。
     *
     * @param Boolean     $fulfilled   是否满足建议
     * @param string      $testMessage 测试建议的消息（当为null且$evaluation为布尔值时，将派生默认消息）
     * @param string      $helpHtml    解决问题的HTML格式帮助文本（当为null且$evaluation为布尔值时，将派生默认帮助）
     * @param string|null $helpText    解决问题的帮助文本（当为null时，将从$helpHtml中推断，即从HTML标签中剥离）
     */
    public function addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        $this->add(new Requirement($fulfilled, $testMessage, $helpHtml, $helpText, true));
    }

    /**
     * 添加一个强制性要求，形式为php.ini配置。
     *
     * @param string           $cfgName    用于ini_get()的配置名称
     * @param Boolean|callback $evaluation 一个布尔值，指示配置是否应评估为true或false，
     *                                      或接收配置值作为参数以确定要求满足情况的回调函数
     * @param Boolean $approveCfgAbsence 如果为true，即使配置选项不存在（即ini_get()返回false），要求也将满足。
     *                                      这对于稍后的PHP版本中已放弃的配置或可选扩展的配置（如Suhosin）很有帮助。
     *                                      例如：您需要一个配置为true，但PHP稍后删除了该配置并将其内部默认设置为true。
     * @param string      $testMessage 测试要求的消息（当为null且$evaluation为布尔值时，将派生默认消息）
     * @param string      $helpHtml    解决问题的HTML格式帮助文本（当为null且$evaluation为布尔值时，将派生默认帮助）
     * @param string|null $helpText    解决问题的帮助文本（当为null时，将从$helpHtml中推断，即从HTML标签中剥离）
     */
    public function addPhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null)
    {
        $this->add(new PhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence, $testMessage, $helpHtml, $helpText, false));
    }

    /**
     * 添加一个可选建议，形式为php.ini配置。
     *
     * @param string           $cfgName    用于ini_get()的配置名称
     * @param Boolean|callback $evaluation 一个布尔值，指示配置是否应评估为true或false，
     *                                      或接收配置值作为参数以确定建议满足情况的回调函数
     * @param Boolean $approveCfgAbsence 如果为true，即使配置选项不存在（即ini_get()返回false），建议也将满足。
     *                                      这对于稍后的PHP版本中已放弃的配置或可选扩展的配置（如Suhosin）很有帮助。
     *                                      例如：您需要一个配置为true，但PHP稍后删除了该配置并将其内部默认设置为true。
     * @param string      $testMessage 测试建议的消息（当为null且$evaluation为布尔值时，将派生默认消息）
     * @param string      $helpHtml    解决问题的HTML格式帮助文本（当为null且$evaluation为布尔值时，将派生默认帮助）
     * @param string|null $helpText    解决问题的帮助文本（当为null时，将从$helpHtml中推断，即从HTML标签中剥离）
     */
    public function addPhpIniRecommendation($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null)
    {
        $this->add(new PhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence, $testMessage, $helpHtml, $helpText, true));
    }

    /**
     * 添加一个要求集合到当前要求集合中。
     *
     * @param RequirementCollection $collection 一个RequirementCollection实例 
     */
    public function addCollection(RequirementCollection $collection)
    {
        $this->requirements = array_merge($this->requirements, $collection->all());
    }

    /**
     * 返回所有要求和建议。
     *
     * @return array 一个Requirement实例数组
     */
    public function all()
    {
        return $this->requirements;
    }

    /**
     * 返回所有强制性要求。
     *
     * @return array 一个Requirement实例数组
     */
    public function getRequirements()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if (!$req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * 返回所有未满足的强制性要求。
     *
     * @return array 一个Requirement实例数组
     */
    public function getFailedRequirements()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && !$req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * 返回所有可选建议。
     *
     * @return array 一个Requirement实例数组
     */
    public function getRecommendations()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if ($req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * 返回所有未满足的可选建议。
     *
     * @return array 一个Requirement实例数组
     */
    public function getFailedRecommendations()
    {
        $array = array();
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && $req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * 返回是否存在php.ini配置问题。
     *
     * @return Boolean 是否存在php.ini配置问题？
     */
    public function hasPhpIniConfigIssue()
    {
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && $req instanceof PhpIniRequirement) {
                return true;
            }
        }

        return false;
    }

    /**
     * 返回php.ini配置文件（php.ini）路径。
     *
     * @return string|false php.ini文件路径
     */
    public function getPhpIniConfigPath()
    {
        return get_cfg_var('cfg_file_path');
    }
}

/**
 * 此类指定所有Pagekit要求和可选建议。
 *
 * @author Tobias Schultze <http://tobion.de>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class PagekitRequirements extends RequirementCollection
{
    const REQUIRED_PHP_VERSION = '7.4';

    /**
     * 初始化要求的构造函数。
     */
    public function __construct($path)
    {
        /* 强制性要求开始 */

        $installedPhpVersion = phpversion();

        $this->addPhpIniRequirement('detect_unicode', false);
        $this->addPhpIniRequirement('allow_url_fopen', true);

        $this->addRequirement(
            version_compare($installedPhpVersion, self::REQUIRED_PHP_VERSION, '>='),
            sprintf('PHP版本必须至少为%s（已安装%s）', self::REQUIRED_PHP_VERSION, $installedPhpVersion),
            sprintf('您正在运行PHP版本"<strong>%s</strong>"，但Pagekit需要至少PHP"<strong>%s</strong>"才能运行。
                在使用Pagekit之前，请升级您的PHP安装，最好升级到最新版本。',
                $installedPhpVersion, self::REQUIRED_PHP_VERSION),
            sprintf('安装PHP %s或更新版本（已安装版本为%s）', self::REQUIRED_PHP_VERSION, $installedPhpVersion)
        );
        $this->addRequirement(
            function_exists('json_encode'),
            'json_encode()必须可用',
            '安装并启用<strong>JSON</strong>扩展。'
        );

        $this->addRequirement(
            extension_loaded('openssl'),
            'OpenSSL必须可用',
            '安装并启用<strong>OpenSSL</strong>扩展。'
        );

        $this->addRequirement(
            function_exists('session_start'),
            'session_start()必须可用',
            '安装并启用<strong>session</strong>扩展。'
        );

        $this->addRequirement(
            function_exists('ctype_alpha'),
            'ctype_alpha()必须可用',
            '安装并启用<strong>ctype</strong>扩展。'
        );

        $this->addRequirement(
            function_exists('token_get_all'),
            'token_get_all()必须可用',
            '安装并启用<strong>Tokenizer</strong>扩展。'
        );

        $this->addRequirement(
            function_exists('simplexml_import_dom'),
            'simplexml_import_dom()必须可用',
            '安装并启用<strong>SimpleXML</strong>扩展。'
        );

        $this->addRequirement(
            function_exists('dom_import_simplexml'),
            'dom_import_simplexml()必须可用',
            '安装并启用<strong>DOM</strong>扩展。'
        );

        $this->addRequirement(
            function_exists('mb_strtolower'),
            'mb_strtolower()必须可用',
            '安装并启用<strong>mbstring</strong>扩展。'
        );

        $this->addRequirement(
            defined('PCRE_VERSION'),
            'PCRE扩展必须可用',
            '安装并启用<strong>PCRE</strong>扩展（版本8.0+）。'
        );

        $this->addRequirement(
            class_exists('ZipArchive'),
            'ZipArchive必须可用',
            '安装并启用<strong>ZIP</strong>扩展。'
        );

        $this->addRequirement(
            class_exists('PDO'),
            'PDO必须可用',
            '安装并启用<strong>PDO</strong>扩展。'
        );

        if (version_compare($installedPhpVersion, '5.6', '>=') && version_compare($installedPhpVersion, '7.0.0', '<')) {
            $this->addRequirement(!(ini_get('display_startup_errors') === "1" && ini_get('always_populate_raw_post_data') !== "-1"),
                '\'display_startup_errors\'必须禁用，\'always_populate_raw_post_data\'必须设置为\'\-1\'',
                '禁用启动错误或在php.ini中设置\'always_populate_raw_post_data\'为\'\-1\''
            );
        }

        if (class_exists('PDO')) {
            $drivers = PDO::getAvailableDrivers();
            $this->addRequirement(
                (in_array('mysql', $drivers) || in_array('sqlite', $drivers)),
                sprintf('PDO必须安装MySQL或SQLite驱动程序（当前可用：%s）', count($drivers) ? implode(', ', $drivers) : 'none'),
                '安装<strong>PDO驱动程序</strong>。'
            );
        }

        $writable_directories = ["$path/tmp", "$path/tmp/cache", "$path/tmp/logs", "$path/tmp/sessions"];

        // 如果config.php不存在，我们需要应用程序的根目录可写。
        if (!file_exists("$path/config.php")) {
            array_unshift($writable_directories, $path);
        }

        foreach ($writable_directories as $dir) {
            $this->addRequirement(
                is_writable($dir),
                "{$dir} 目录必须可写",
                "确保Web服务器可以写入 \"<strong>{$dir}</strong>\" 目录。"
            );
        }

        $this->addRequirement(
            file_exists("$path/.htaccess"),
            ".htaccess 文件不存在",
            "确保 <strong>.htaccess</strong> 文件已上传，有时使用FTP/SFTP上传时会遗漏隐藏文件。"
        ); 

        if (function_exists('opcache_invalidate') && ini_get('opcache.enable')) {
            $this->addPhpIniRequirement('opcache.load_comments', true, true);
            $this->addPhpIniRequirement('opcache.save_comments', true, true);
        }

        /* 可选建议开始 */

        $this->addRecommendation(
            function_exists('curl_init'),
            'curl_init() 必须可用',
            '安装并启用 <strong>cURL</strong> 扩展。'
        );

        $this->addRecommendation(
            function_exists('iconv'),
            'iconv() 必须可用',
            '安装并启用 <strong>iconv</strong> 扩展。'
        );

        $this->addRecommendation(
            function_exists('utf8_decode'),
            'utf8_decode() 必须可用',
            '安装并启用 <strong>XML Parser</strong> 扩展。'
        );

        if (extension_loaded('apcu')) {
            $this->addRecommendation(
                version_compare(phpversion('apcu'), '4.0.2', '>='),
                'APCu 版本必须至少为 4.0.2',
                '升级 <strong>APCu</strong> 扩展（4.0.2+）。'
            );
        }

        if (function_exists('apc_store') && ini_get('apc.enabled')) {
            $this->addRequirement(
                version_compare(phpversion('apc'), '3.1.13', '>='),
                'APC 版本必须至少为 3.1.13 当使用 PHP 5.4 时',
                '升级 <strong>APC</strong> 扩展（3.1.13+）。'
            );
        }

        $accelerator = (function_exists('apc_store') && ini_get('apc.enabled'))
                        || (function_exists('eaccelerator_put') && ini_get('eaccelerator.enable'))
                        || (function_exists('opcache_invalidate') && ini_get('opcache.enable'))
                        || function_exists('xcache_set');

        $this->addRecommendation(
            $accelerator,
            '应该安装PHP加速器',
            '安装并启用 <strong>PHP 加速器</strong> 如 APC（强烈推荐）。'
        );

        $this->addPhpIniRecommendation('short_open_tag', false);
        $this->addPhpIniRecommendation('magic_quotes_gpc', false, true);
        $this->addPhpIniRecommendation('register_globals', false, true);
        $this->addPhpIniRecommendation('session.auto_start', false);
    }
}

return new PagekitRequirements($path);
