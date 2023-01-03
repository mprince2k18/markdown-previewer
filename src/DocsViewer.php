<?php
/**
 * File containing the class {@see \Mprince\MarkdownViewer\DocsViewer}.
 *
 * @package MarkdownViewer
 * @see \Mprince\MarkdownViewer\DocsViewer
 */

declare(strict_types=1);

namespace Mprince\MarkdownViewer;

use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\File;
use AppUtils\OutputBuffering;use AppUtils\OutputBuffering_Exception;

/**
 * Renders the documentation viewer UI, using the
 * list of documents contained in the manager instance.
 *
 * @package MarkdownViewer
 * @author Mohammad Prince <mprince2k16@gmail.com>
 */
class DocsViewer
{
    public const ERROR_NO_DOCUMENTS_AVAILABLE = 82001;

    private string $title = 'Documentation';
    private string $menuLabel = 'Available documents';
    private string $spaceName = '';
    private DocsManager $docs;
    private bool $darkMode = false;
    private string $vendorURL;
    private string $packageURL;

    /**
     * @param DocsManager $manager
     * @param string $vendorURL
     * @throws DocsException
     * @see DocsViewer::ERROR_NO_DOCUMENTS_AVAILABLE
     */
    public function __construct(DocsManager $manager, string $vendorURL)
    {
        $this->docs = $manager;
        $this->vendorURL = rtrim($vendorURL, '/');

        if(!$this->docs->hasFiles()) {
            throw new DocsException(
                'Cannot start viewer, the are no documents to display.',
                '',
                self::ERROR_NO_DOCUMENTS_AVAILABLE
            );
        }
    }

    public function makeDarkMode() : DocsViewer
    {
        $this->darkMode = true;
        return $this;
    }

    /**
     * Sets the title of the document and the navigation label.
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title) : DocsViewer
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Sets the label of the menu item listing all the available documents.
     *
     * @param string $label
     * @return $this
     */
    public function setMenuLabel(string $label) : DocsViewer
    {
        $this->menuLabel = $label;
        return $this;
    }

    public function getActiveFileID() : string
    {
        if(isset($_REQUEST['doc']) && $this->docs->idExists($_REQUEST['doc'])) {
            return $_REQUEST['doc'];
        }

        return $this->docs->getFirstFile()->getID();
    }

    public function getActiveFile() : DocFile
    {
        return $this->docs->getByID($this->getActiveFileID());
    }

    public function setSpaceName(string $space) : DocsViewer
    {
        $this->spaceName = $space;
        return $this;
    }

    public function display() : void
    {
        $parser = new DocParser($this->getActiveFile());

?>


<?php

  if (function_exists('getSpaceBySlug')) {
    if (getSpaceBySlug($this->spaceName)->logo) {
      $fav_icon = asset(getSpaceBySlug($this->spaceName)->logo ?? env('APP_URL') . '/' ."/favicon.png");
    }else {
      $fav_icon =  env('APP_URL') . '/' ."/favicon.png";
    }
  } else {
      $fav_icon =  env('APP_URL') . '/' ."/favicon.png";
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="keywords" content="">

    <title><?php echo $this->title ?></title>

    <!-- Styles -->
    <link href="http://thetheme.io/thedocs/assets/css/page.min.css" rel="stylesheet">
    <link href="http://thetheme.io/thedocs/assets/css/style.css" rel="stylesheet">

    <!-- Favicons -->
    <link rel="apple-touch-icon" href="http://thetheme.io/thedocs/assets/img/apple-touch-icon.png">
    <link rel="icon" href="<?php echo $fav_icon; ?>">

    
  </head>

  <style>
    .anchor {
        display: none;
    }

    .nav-level-1{
      font-size: 12px;
    }
  </style>

  <body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-stick-dark" data-navbar="static">
      <div class="container">

        <div class="navbar-left">
          <button class="navbar-toggler" type="button">&#9776;</button>
          <a class="navbar-brand text-dark" href="#">
            <?php
                if (function_exists('getSpaceBySlug')) {
                  if (getSpaceBySlug($this->spaceName)->logo) {
                    echo '<img class="logo-dark w-75" width="100" src="'. asset(getSpaceBySlug($this->spaceName)->logo ?? "/public/logo.png") .'" alt="'. $this->spaceName .'">';
                  }else {
                    echo ucwords(str_replace('-', ' ', $this->spaceName));
                  }
                } else {
                    echo ucwords(str_replace('-', ' ', $this->spaceName));
                }
            ?>
          </a>
        </div>

        <section class="navbar-mobile">
          <span class="navbar-divider d-mobile-none"></span>

          <ul class="nav nav-navbar">

            <li class="nav-item">
                <?php
                  if (function_exists('getSpaceBySlug')) {
                      echo 'v' . getSpaceBySlug($this->spaceName)->version ?? "1.0";
                  } else {
                      echo "v1.0";
                  }
                ?>
            </li>

          </ul>
        </section>


      </div>
    </nav><!-- /.navbar -->


    <!-- Main Content -->
    <main class="main-content">
      <div class="container">
        <div class="row">
          
          <div class="col-md-3 col-xl-3">
            <hr class="d-md-none my-0">
            <aside class="sidebar sidebar-expand-md sidebar-sticky pr-md-4 br-1">
              <ul class="nav nav-sidebar nav-sidebar-hero" data-accordion="true">
                 <?php 
                  if ($this->spaceName != null && File::exists(base_path('files/'. $this->spaceName .'/_sidebar.md'))) {
                      echo Markdown::parse(File::get(base_path('files/'. $this->spaceName .'/_sidebar.md'))); 
                  }
                 ?>
              </ul>
            </aside>
          </div>

          <div class="col-md-7 col-xl-7 ml-md-auto py-6">
             <?php echo $parser->render(); ?>
          </div>

          <div class="col-md-2 col-xl-2">
            <hr class="d-md-none my-0">
            <aside class="sidebar">
                <ul class="nav nav-sidebar nav-sidebar-hero" data-accordion="true">
                    <?php echo $this->renderMenu($parser->getHeaders()); ?>
                </ul>
            </aside>
          </div>

        </div>
      </div>
    </main><!-- /.main-content -->


    <!-- Footer -->
    <footer class="footer py-5 text-center text-lg-center">
      <div class="container">
        <div class="row gap-y">
          <div class="col-lg-12 text-center">
            <p>
              <a href="#">
                <?php
                    if (function_exists('getSpaceBySlug')) {
                      if (getSpaceBySlug($this->spaceName)->logo) {
                        echo '<img class="logo-dark"src="'. asset(getSpaceBySlug($this->spaceName)->logo ?? "/public/logo.png") .'" alt="'. $this->spaceName .'">';
                      }else {
                        echo ucwords(str_replace('-', ' ', $this->spaceName));
                      }
                    } else {
                        echo ucwords(str_replace('-', ' ', $this->spaceName));
                    }
                ?>
              </a><br>
              © <?php echo date("Y"); ?> Powered By 
              
              <b>
                <?php 
                if(function_exists('application')) { 
                  echo application('site_name');
                }else {
                    echo 'The Code Studio';
                } ?>
            </b>.
            </p>
          </div>
        </div>
      </div>
    </footer><!-- /.footer -->


    <!-- Scripts -->
    <script src="http://thetheme.io/thedocs/assets/js/page.min.js"></script>
    <script>
            const links = document.querySelectorAll('a[href]');
            links.forEach(link => {
                link.href = link.href.replace(/\.md$/, '');
            });
        </script>
  </body>
</html>




<?php
    }

    public function setPackageURL(string $url) : DocsViewer
    {
        $this->packageURL = rtrim($url, '/');
        return $this;
    }

    private function getPackageURL() : string
    {
        if(!empty($this->packageURL)) {
            return $this->packageURL;
        }

        return $this->vendorURL.'/mistralys/markdown-viewer';
    }

    /**
     * @param DocHeader[] $headers
     * @return string
     * @throws OutputBuffering_Exception
     */
    private function renderMenu(array $headers) : string
    {
        OutputBuffering::start();

        ?>
        <ul class="nav-level-0">
            <?php
            foreach ($headers as $header)
            {
                echo $header->render();
            }
            ?>
        </ul>
        <?php

        return OutputBuffering::get();
    }
}
