<?php
/**
 * Class SimpleRtfProcessor
 *
 * @author MICH <mich.chmielewski@gmail.com>
 *   replacing in RTF file upper case variables:
 *   [SOMEVALUE]
 *   [SOMEVALUE_C]
 *   [SOMEVALUE_L]
 *
 * Example usage
 *    $replaceVars = array(
 *           'someValue' => 'Lorem ipsum...',
 *  	     'someValue_C  => 'Lorem ipsum... \r\n Lorem ipsum...', # sufix "_C" clear new lines 'Lorem ipsum... Lorem ipsum...'
 *           'someValue' => "array('Lorem ipsum...','Lorem ipsum...','Lorem ipsum...')", # each in new line
 *           'someValue_L => "array('Lorem ipsum...','Lorem ipsum...','Lorem ipsum...')", # sufix "_L" continue list
 *	);
 *		
 *	try{
 *		$file = new SimpleRtfProcessor($replaceVars, "file_template.rtf");
 *		$file->getFile('proccessedFileName.rtf'); 
 *	} catch(Exception $e) {
 *		echo $e->getMessage();
 *	}
 
 *	try{
 *		$file = new SimpleRtfProcessor($replaceVars, "file_template.rtf");
		$file->setWorkDir('./upload/');
		echo '<a href="' . $x->getFilePath('proccessedFileName.rtf'). '" download>Download file</a>';
 *	} catch(Exception $e) {
 *		echo $e->getMessage();
 *	}
 */

class SimpleRtfProcessor {
	
	private const REPLACMENTS = [
							   '\\' => "\\\\",
							   '{'  => "\{",
							   '}'  => "\}"
								];	
	private const EXTENTION = ["rtf", "doc", "docx"];
	private $documentContent;
	private $replaceVariables = array();
	private $workDir;
	
	public function __construct($replaceVariables, $rtfDocument) {
		$this->replaceVariables = $replaceVariables;
		$this->setFile($rtfDocument);
		$this->proccess();
		$this->workDir = sys_get_temp_dir();
	}
	
	public function setWorkDir($workDir) {
		$this->workDir = $workDir;
	}
	
	
	public function getFile($fileName) {
		header("Content-type: application/msword");
		header("Content-disposition: inline; filename=" . $this->getFileName($fileName));
		header("Content-length: " . strlen($this->documentContent));
		echo $this->documentContent;
	}
	
     /**
     * @throws \Exception
     */
	public function getFilePath($fileName) {
		try {
			return $this->generateRtfFile($this->getFileName($fileName));
		} catch(Exception $e) {
			throw new Exception($e->getMessage());		
		}
	}

    /**
     * @throws \Exception
     */
	 
	private function setFile($rtfDocument){

		$this->documentContent = @file_get_contents($rtfDocument);
		if (!$this->documentContent) {
			throw new Exception('RTF Template file not found!');
		}
	}
	
	private function proccess(){
		
		foreach($this->replaceVariables as $key=>$value) {
			
                        $key = strtoupper($key);
			
			if (is_array($value)){
				foreach ($value as $k=>$v) {
					$value[$k] = trim($this->escapeValue($v));
				}
				$type = (substr($key,-2) == '_L')? '{\line\par}' : '{\line}';
				$this->replaceValue($key, implode($type,$value));
			} else {
				$type = (substr($key,-2) == '_C')? ' ' : '{\line}';
				$v = str_replace("\r\n", $type, trim($this->escapeValue($value)));
				$this->replaceValue($key, $v);
			} 
        	}
	}
	
	private function escapeValue($value) {
		foreach(self::REPLACMENTS as $orig => $replace) {
			$value = str_replace($orig, $replace, $value);
		}
		return $value;
	}
	
	private function search($key) {
		return "[".$key."]";
	}
	
	private function replaceValue($key, $value){
		$this->documentContent = str_replace($this->search($key), $value, $this->documentContent);
	}
	
    /**
     * @throws \Exception
     */
	private function generateRtfFile($fileName){
		$filePath = $this->workDir . $fileName;
		$newFile = @fopen($filePath, 'w');
		if (!$newFile) {
			throw new Exception("Failed to open stream: No such file or directory");
		}
		fwrite($newFile, $this->documentContent);
		fclose($newFile);
		return $filePath;
	}
	
	private function getFileName($fileName){
		$ext = pathinfo($fileName, PATHINFO_EXTENSION);
		if ($ext !=null && in_array($ext, self::EXTENTION)) {
			return $fileName;
		}
		return $fileName . '.rtf';
	}
	
}
