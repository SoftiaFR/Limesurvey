<?php

namespace archon810;

class SmartDOMDocumentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * This test functions shows an example of SmartDOMDocument in action.
     * A sample HTML fragment is loaded.
     * Then, the first image in the document is cut out and saved separately.
     * It also shows that Russian characters are parsed correctly.
     *
     */
    public function testHTML()
    {
        $content = '<div class=\'class1\'><img src=\'http://www.google.com/favicon.ico\' />Some Text<p>русский</p></div>';

        $content_doc = new SmartDOMDocument();
        $content_doc->loadHTML($content);

        try {
            $first_image = $content_doc->getElementsByTagName("img")
                                       ->item(0);

            if ($first_image) {
                $first_image->parentNode->removeChild($first_image);

                $content = $content_doc->saveHTMLExact();

                $image_doc = new SmartDOMDocument();
                $image_doc->appendChild($image_doc->importNode($first_image, true));
                $image = $image_doc->saveHTMLExact();
            }
        } catch (\Exception $e) {
        }

        $expect = '<div class="class1">Some Text<p>&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;</p></div>' . "\n";

        $this->assertEquals($expect, $content);
    }
}
