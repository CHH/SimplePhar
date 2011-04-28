<?php


namespace SimplePhar;

use Phar,
    SplStack;

class Compiler
{
    protected $classMap = array();

    protected $basePath;
    protected $filePaths;
    protected $cliStub;
    protected $webStub;
    protected $licenseFile = "LICENSE";
    protected $classMapAutoloaderEnabled = false;
    protected $distPath;
    
    function __construct()
    {
        $this->filePaths = new SplStack;
        $this->basePath = getcwd();
    }
    
    function setBasePath($path)
    {
        $this->basePath = $path;
        return $this;
    }
    
    function setDistPath($distPath)
    {
        $this->distPath = $distPath;
        return $this;
    }
    
    function setFilePaths(array $paths) 
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
        return $this;
    }
    
    /**
     * Adds the path to the stack of file paths
     *
     * @param string $path
     * @return Compiler
     */
    function addPath($path)
    {
        $this->filePaths->push($path);
        return $this;
    }
    
    function setCliStub($file) 
    {
        $this->cliStub = $file;
        return $this;
    }
    
    function setWebStub($file)
    {
        $this->webStub = $file;
        return $this;
    }
    
    /**
     * Sets the path to the license file
     *
     * @param  string $licenseFile
     * @return Compiler
     */
    function setLicenseFile($licenseFile)
    {
        $this->licenseFile = $licenseFile;
        return $this;
    }
    
    /**
     * If turned on generates a static autoloader by scanning the file paths for Classes
     * and Interfaces
     *
     * @param  bool $enable
     * @return Compiler
     */
    function setGenerateAutoloader($enable = true) 
    {
        $this->classMapAutoloaderEnabled = $enable;
        return $this;
    }
    
    /**
     * Start the compilation
     *
     * @param string $distPath Path to the file to generate
     */
    function compile($distPath = null)
    {
        $distPath = $this->basePath . DIRECTORY_SEPARATOR . ($distPath ?: $this->distPath);
        
        if (!is_dir(dirname($distPath))) {
            @mkdir(dirname($distPath));
        }
        
        $phar = new Phar($distPath, 0, "Foo");
        $phar->setSignatureAlgorithm(Phar::SHA1);
        $phar->startBuffering();

        $files = array();
        
        foreach ($this->filePaths as $path) {
            $files = array_merge($files, $this->findFiles($path));
        }
        
        foreach ($files as $file) {
            $this->addFile($phar, $file, $this->basePath);
        }
        
        $licenseFile = realpath($this->basePath . DIRECTORY_SEPARATOR . $this->licenseFile);
        if ($licenseFile) {
            $phar["LICENSE"] = file_get_contents($licenseFile);
        }
        
        if ($this->classMapAutoloaderEnabled) {
            $phar["_autoload.php"] = $this->getClassmapAutoloader();
        }
        
        $cliStub = $this->generateStub($this->cliStub);
        $phar["_cli_stub.php"] = $cliStub;
        
        if ($this->webStub) {
            $phar["_web_stub.php"] = $this->generateStub($this->webStub);
        } else {
            $phar["_web_stub.php"] = $cliStub;
        }
        
        $phar->setDefaultStub("_cli_stub.php", "_web_stub.php");
        $phar->stopBuffering();
    }
    
    protected function generateStub($require)
    {
        $templ = <<<'TEMPLATE'
<?php
require_once "%s";
__HALT_COMPILER();
TEMPLATE;
        
        return sprintf($templ, $require);
    }
    
    /**
     * Generates an autoloader for the files in the class map
     *
     * @return string PHP Code
     */
    protected function getClassmapAutoloader()
    {
        $map = var_export($this->classMap, true);

        $code = <<<'TEMPLATE'
<?php
$map = %s;
spl_autoload_register(function($class) use ($map) {
    if (isset($map[$class])) {
        require_once __DIR__ . $map[$class];
    }
});
TEMPLATE;

        return sprintf($code, $map);
    }

    /**
     * Returns all PHP Files in $dir
     *
     * @param string $dir
     * @return array
     */
    function findFiles($dir)
    {
        $files = array();
        
        $dir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $iterator = new \RecursiveDirectoryIterator($dir);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            if (!$file->isFile() or substr($file->getFileName(), -4, 4) !== ".php") {
                continue;
            }
            $files[] = (string) $file;
        }

        return $files;
    }

    /**
     * Adds contents of the supplied file to the PHAR
     *
     * @param string $file
     */
    protected function addFile(Phar $phar, $file, $basePath = '')
    {
        $pharPath = str_replace($basePath, '', $file);
        $content  = trim(file_get_contents($file));

        if (".php" == substr($file, -4, 4)) {
            $content = $this->stripComments($content);
            $class   = $this->findClass($content);

            if ($class) {
                // Register symbol in class map for autoloader generation
                $this->classMap[$class] = $pharPath;
            }
        }

        $phar->addFromString($pharPath, $content);
    }

    /**
     * Looks in the supplied content for a class definition
     *
     * @param string $content
     * @return bool|string Returns the fully-qualified name of the class if found
     */
    protected function findClass($content)
    {
        $namespace = "/namespace ([a-zA-Z0-9_\\\\]+);/";

        if (preg_match($namespace, $content, $matches)) {
            $currentNs = $matches[1];
        }

        $class = "/(class|interface) ([a-zA-Z0-9_\\\\]+)/";

        if (preg_match($class, $content, $matches)) {
            $symbol = (empty($currentNs) ? '' : $currentNs . '\\') . $matches[2];
            return $symbol;
        }
        return false;
    }

    /**
     * Strips all comments from the supplied string of PHP code
     *
     * @param string $code
     */
    protected function stripComments($code)
    {
        $newStr = '';

        $commentTokens = array(T_COMMENT, T_DOC_COMMENT);
        $tokens = token_get_all($code);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], $commentTokens))
                    continue;

                $token = $token[1];
            }

            $newStr .= $token;
        }

        return $newStr;
    }
}

