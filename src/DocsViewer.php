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

    /**
     * > This function sets the dark mode to true
     * 
     * @return DocsViewer The DocsViewer object.
     */
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

    /**
     * It returns the ID of the file that should be displayed
     * 
     * @return string The ID of the active file.
     */
    public function getActiveFileID() : string
    {
        if(isset($_REQUEST['doc']) && $this->docs->idExists($_REQUEST['doc'])) {
            return $_REQUEST['doc'];
        }

        return $this->docs->getFirstFile()->getID();
    }

    /**
     * > Returns the active file
     * 
     * @return DocFile The active file.
     */
    public function getActiveFile() : DocFile
    {
        return $this->docs->getByID($this->getActiveFileID());
    }

    /**
     * `setSpaceName()` sets the space name
     * 
     * @param string space The name of the space you want to display.
     * 
     * @return DocsViewer The DocsViewer object.
     */
    public function setSpaceName(string $space) : DocsViewer
    {
        $this->spaceName = $space;
        return $this;
    }

    /**
     * It will display the page.
     */
    public function display() : void
    {
        /* Creating a new DocParser object and passing the active file to it. */
        $parser = new DocParser($this->getActiveFile());

?>


<?php

  /* Checking if the function getSpaceBySlug exists, if it does, it will check if the space has a logo,
  if it does, it will set the fav_icon variable to the logo, if not, it will set the fav_icon
  variable to the default favicon. */
  if (function_exists('getSpaceBySlug')) {
    if (getSpaceBySlug($this->spaceName)->logo) {
      $fav_icon = asset(getSpaceBySlug($this->spaceName)->logo ?? env('APP_URL') . '/' ."/favicon.png");
    }else {
      $fav_icon =  env('APP_URL') . '/' ."/favicon.png";
    }
  } else {
      $fav_icon =  env('APP_URL') . '/' ."/favicon.png";
  }

  
  /* Getting the space id of the current space. */
  $current_space_id = getSpaceBySlug($this->spaceName)->id;


  /* Checking if the current page is the homepage or not. If it is the homepage, it will get the
  homepage slug. If it is not the homepage, it will get the current page slug. */
  if (\Illuminate\Support\Facades\Route::current()->parameter('page_slug') == null) {
      $space_page_slug = space_homepage($current_space_id)->slug;
  }else {
      $space_page_slug = \Illuminate\Support\Facades\Route::current()->parameter('page_slug');
  }

  /* Getting the current page id by using the current space id and the space page slug. */
  $current_page_id = getSpaceMenuBySlug($current_space_id, $space_page_slug)->id;

  /* Getting the next page id from the space_pagination function. */
  $next = space_pagination($this->spaceName, $current_page_id)['next'];
  /* Getting the previous page id from the space_pagination function. */
  $previous = space_pagination($this->spaceName, $current_page_id)['previous'];

  /* Checking if the space exists and if it does, it will get the version of the space. */
  $version = 'v' . getSpaceBySlug($this->spaceName)->version ?? "1.0";

  $filename = base_path('files/' . \Illuminate\Support\Facades\Route::current()->parameter('space_name') . '/' . \Illuminate\Support\Facades\Route::current()->parameter('page_slug') . '.md');

  $markdown = file_get_contents($filename);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="keywords" content="">

    <title><?php echo ucwords(\Illuminate\Support\Str::slug($this->title, ' ', true)) . ' | ' . ucwords($space_page_slug); ?></title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
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

    .nav-level-1 {
        font-size: 13px;
    }

    .icon-center {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .right-sidebar li, 
    .left-sidebar li {
      list-style: none;
    }

    .right-sidebar p {
      margin: 0;
    }

    .left-sidebar p {
      margin: 5px 0;
    }

  </style>

  <script>
    "use strict"

    function myStyle() {

      var leftSidebarUl = document.querySelector('.left-sidebar ul');
      leftSidebarUl.classList.add('h5');

      var rightSidebarUl = document.querySelector('.right-sidebar ul');
      rightSidebarUl.classList.add('toc');

    }
  </script>

  <body onload="myStyle()">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-stick-dark" data-navbar="static">
      <div class="container">

        <div class="navbar-left">
          <button class="navbar-toggler" type="button">&#9776;</button>
          <a class="navbar-brand text-dark" href="<?php route('space.page', [$version, $this->spaceName, space_homepage($current_space_id)->slug ?? null]) ?>">
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

            <li class="nav-item text-monospace">
                <?php
                  if (function_exists('getSpaceBySlug')) {
                      echo $version;
                  } else {
                      echo "v1.0";
                  }
                ?>
            </li>

          </ul>
        </section>

        <a class="text-monospace w-100 text-right text-muted" href="<?php echo route('frontend'); ?>" target="_blank">
          Powered By 
        <?php 
          if(function_exists('application')) { 
            echo application('site_name');
          }else {
              echo 'The Code Studio';
        } ?>
        </a>

      </div>
    </nav><!-- /.navbar -->

    <!-- Main Content -->
    <main class="main-content">
      <div class="container">
        <div class="row">
          
          <div class="col-md-3 col-xl-3">
            <hr class="d-md-none my-0">
            <aside class="sidebar sidebar-expand-md sidebar-sticky pr-md-4 br-1 left-sidebar">
                 <?php 
                  if ($this->spaceName != null && File::exists(base_path('files/'. $this->spaceName .'/_sidebar.md'))) {
                      echo Markdown::parse(File::get(base_path('files/'. $this->spaceName .'/_sidebar.md'))); 
                  }
                 ?>
            </aside>
          </div>

          <div class="col-md-7 col-xl-7 ml-md-auto py-6">
             
            <?php 
                if (empty($markdown)) {
                    echo '<h1 class="h1">No Content Found</h1>';
                }
            ?>

             <?php echo $parser->render(); ?>

              <div class="row">

               <?php if($previous != null) {?>
                <div class="col-md-<?php echo ($next == null) ? 12 : 6; ?>">
                  <a href="<?php echo route('space.page', ['v' . getSpaceBySlug($this->spaceName)->version ?? "1.0", $this->spaceName, getSpaceMenuById($previous)->slug]); ?>">
                    <div class="card border">
                      <div class="card-body d-flex justify-content-between">
                        <div class="icon-center">
                            <i class="fa fa-long-arrow-left text-dark"></i>
                        </div>
                        <div>
                          <small class="text-muted">Previous</small>
                          <br>
                          <span class="text-dark h6"><?php echo ucwords(getSpaceMenuById($previous)->label); ?></span>
                        </div>
                      </div>
                    </div>
                  </a>
                </div> 
                <?php } ?> 


                <?php if($next != null) {?>

                <div class="col-md-<?php echo ($previous == null) ? 12 : 6; ?>">
                  <a href="<?php echo route('space.page', ['v' . getSpaceBySlug($this->spaceName)->version ?? "1.0", $this->spaceName, getSpaceMenuById($next)->slug]); ?>">
                    <div class="card border">
                        <div class="card-body d-flex justify-content-between">
                          <div>
                            <small class="text-muted">Next</small>
                            <br>
                            <span class="text-dark h6"><?php echo ucwords(getSpaceMenuById($next)->label); ?></span>
                          </div>
                          <div class="icon-center">
                              <i class="fa fa-long-arrow-right text-dark"></i>
                          </div>
                        </div>
                    </div>
                  </a>
                  
                </div>
                
                <?php } ?>
              </div>

          </div>

          <div class="col-md-2 col-xl-2">
            <hr class="d-md-none my-0">
            <aside class="sidebar right-sidebar">
              <strong class="h6 fs-14 font-weight-bold">ON THIS PAGE</strong>
                  <?php echo $this->renderMenu($parser->getHeaders()); ?>
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
              <a href="<?php route('space.page', [$version, $this->spaceName, space_homepage($current_space_id)->slug ?? null]) ?>">
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
