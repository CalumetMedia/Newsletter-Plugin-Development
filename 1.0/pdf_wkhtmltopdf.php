<?php
define("WKHTMLTOPDF_VERSION", "0.9.9");

/*if (WKHTMLTOPDF_VERSION === "0.9.9") {
	define('PDF_COMMAND_HEADER_OPTION', '--margin-top 15mm --header-html %s ');
}
else {
	define('PDF_COMMAND_HEADER_OPTION', '--margin-top 15mm ');
}

define('PDF_COMMAND', 'wkhtmltopdf --margin-left 15mm --margin-right 15mm --margin-bottom 15mm %s %s %s');
*/

if (WKHTMLTOPDF_VERSION === "0.9.9") {
        define('PDF_COMMAND_HEADER_OPTION', '--margin-top 10mm --header-html %s ');
}
else {
        define('PDF_COMMAND_HEADER_OPTION', '--margin-top 5mm ');
}

define('PDF_COMMAND', 'wkhtmltopdf --margin-left 5mm --margin-right 5mm --margin-bottom 5mm %s %s %s');


/**
 * wkhtmltopdf PDF helper
 *
 * @package PDF
 * @author  Dan Hulton
 */
class pdf {
	/**
	 * Converts the provided content to a PDF and returns the temporary filename it resides in.
	 * 
	 * @param string $content The content to output as a PDF.
	 * @param string $header  The header to write to the PDF.
	 *
	 * @return string|false Either false or the filename of the tempfile the PDF is in.
	 */
	public static function create($content, $header = null) {
		$success = false;
		
        // Assume no options to the wkhtmltopdf command
		$options = "";
        
        // Create temporary file for HTML
        $temp_html  = self::secure_tmpname('.html', 'html');
		if (false === $temp_html) {
			return false;
		}
		
		// Write HTML to temporary file
		if (false === file_put_contents($temp_html, $content)) {
			return false;
		}
		
		// Create temporary file for header (if necessary)
		if (isset($header)) {
			// Create temporary file for the header
			$temp_header = self::secure_tmpname('.html', 'header');
			if (false === $temp_header) {
				return false;
			}
			
			// Write header to temporary file
			if (false === file_put_contents($temp_header, $header)) {
				return false;
			}
			
			$options = sprintf(PDF_COMMAND_HEADER_OPTION, $temp_header);
		}
		
		// Create temporary file for PDF
        $temp_pdf   = self::secure_tmpname('.pdf', 'pdf');
		if (false === $temp_pdf) {
			return false;
		}
		
		// Create temporary PDF file
		$output = array();
		exec(sprintf(PDF_COMMAND, $options, $temp_html, $temp_pdf), $output);

		// If there were any error messages
		if (count($output) > 0) {
			return false;
		}
		
        // Clean up temporary files
        @unlink($temp_html);
        @unlink($temp_header);
        
        return $temp_pdf;
	}
	
    /**
     * Converts the provided content to a PDF and sends it as a download to the user's browser.
     *
     * @param string $content  The content to output as a PDF.
	 * @param string $header  The header to write to the PDF.
     *
     * @return boolean
     */
    public static function preview($content, $header = null) {
		$temp_pdf = pdf::create($content, $header);
		if (false !== $temp_pdf) {
			// If we can get the size of the temporary PDF file
			$filesize = filesize($temp_pdf);
			if (false !== $filesize) {
				// Put out headers for a download
				header('Content-Description: File Transfer');
				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="newsletter-sample.pdf"');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . $filesize);
				
				// Output the temporary PDF, return if we were able to
				return (false !== readfile($temp_pdf));
			}
		}
	}
	/***************************
	 *Get temporate pdf file and provide editor preview
	 *@param $temp_pdf temp pdf file path
	 *@$content you can create new pdf file
	 *
	 * provide downloading file.
	 * @author Peter Du <pdu@hilltimes.com
	 ***************************/
    
    
    public static function preview_after_generate($temp_pdf="",$content="",$header="")
	{
		$newfilename = date("mdy")."_".strtolower(CURRENT_SITE).".pdf";

		$temp_pdf = $_SESSION['temp_pdf_file'];

		if (false !== $temp_pdf) {
			// If we can get the size of the temporary PDF file
			 $filesize = filesize($temp_pdf);
			
			if (false !== $filesize) {
				// Put out headers for a download
				header('Content-Description: File Transfer');
				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="'.$newfilename.'"');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . $filesize);
				
				// Output the temporary PDF, return if we were able to
				return (false !== readfile($temp_pdf));
			}
		}
	}
    /**
     * Create a secure tempfile with extensions and prefixes.
     *
     * @param string $postfix The extension of the file to create.
     * @param string $prefix  The prefix of the file to create.
     * @param string $dir     The directory to create the file in.
     *
     * @return string|false
     */
    private static function secure_tmpname($postfix = '.tmp', $prefix = 'tmp', $dir = null) {
        // validate arguments
        if (! (isset($postfix) && is_string($postfix))) {
            return false;
        }
        if (! (isset($prefix) && is_string($prefix))) {
            return false;
        }
        if (! isset($dir)) {
            $dir = sys_get_temp_dir();
        }
        
        // find a temporary name
        $tries = 1;
        do {
            // get a known, unique temporary file name
            $sysFileName = tempnam($dir, $prefix);
            if ($sysFileName === false) {
                return false;
            }
            
            // tack on the extension
            $newFileName = $sysFileName . $postfix;
            if ($sysFileName == $newFileName) {
                //Kohana::log('debug', $sysFileName);
                return $sysFileName;
            }
            
            // move or point the created temporary file to the new filename
            // NOTE: these fail if the new file name exist
            $newFileCreated = (self::isWindows() ? @rename($sysFileName, $newFileName) : @link($sysFileName, $newFileName));
            if ($newFileCreated) {
				chmod($newFileName, 0777);
                return $newFileName;
            }
            
            unlink ($sysFileName);
            $tries++;
        } while ($tries <= 5);
        
        return false;
    }
    
    /**
     * Indicates whether this script is running on Windows.
     *
     * @return boolean
     */
    private static function isWindows() {
        return (DIRECTORY_SEPARATOR == '\\' ? true : false);
    }    
}