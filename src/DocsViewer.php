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
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class DocsViewer
{
    public const ERROR_NO_DOCUMENTS_AVAILABLE = 82001;

    private string $title = 'Documentation';
    private string $menuLabel = 'Available documents';
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

    public function display() : void
    {
        $parser = new DocParser($this->getActiveFile());

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
    <link rel="icon" href="http://thetheme.io/thedocs/assets/img/favicon.png">
  </head>

  <style>
    .anchor {
        display: none;
    }
  </style>

  <body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-stick-dark" data-navbar="static">
      <div class="container">

        <div class="navbar-left">
          <button class="navbar-toggler" type="button">&#9776;</button>
          <a class="navbar-brand" href="../index.html">
            <img class="logo-dark" src="http://thetheme.io/thedocs/assets/img/logo-dark.png" alt="logo">
            <img class="logo-light" src="http://thetheme.io/thedocs/assets/img/logo-light.png" alt="logo">
          </a>
        </div>

        <section class="navbar-mobile">
          <span class="navbar-divider d-mobile-none"></span>

          <ul class="nav nav-navbar">

            <li class="nav-item">
              <a class="nav-link" href="#">Layouts <span class="arrow"></span></a>
              <nav class="nav">
                <a class="nav-link" href="../layout/general-1.html">General 1</a>
                <a class="nav-link" href="../layout/general-2.html">General 2</a>
                <a class="nav-link" href="../layout/general-3.html">General 3</a>
                <a class="nav-link" href="../layout/minimal-1.html">Minimal 1</a>
                <a class="nav-link" href="../layout/minimal-2.html">Minimal 2</a>
                <a class="nav-link" href="../layout/one-page-1.html">One Page</a>
              </nav>
            </li>

          </ul>
        </section>

        <a class="btn btn-sm btn-round btn-success" href="https://themeforest.net/item/thedocs-online-documentation-template/13070884?license=regular&open_purchase_for_item_id=13070884&purchasable=source&ref=thethemeio">Purchase</a>

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
                 <?php echo Markdown::parse(File::get(base_path('teleman/_sidebar.md'))); ?>
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
    <footer class="footer py-5 text-center text-lg-left">
      <div class="container">
        <div class="row gap-y">
          <div class="col-lg-3 text-center">
            <p>
              <a href="#"><img src="http://thetheme.io/thedocs/assets/img/logo-dark.png" alt="logo"></a><br>
              © 2019 <a href="http://thetheme.io">TheThemeio</a>.
            </p>
            <div class="social mt-3">
              <a class="social-facebook" href="#"><i class="fa fa-facebook-official"></i></a>
              <a class="social-twitter" href="#"><i class="fa fa-twitter"></i></a>
              <a class="social-github" href="#"><i class="fa fa-github"></i></a>
            </div>
            <hr class="d-lg-none">
          </div>

          <div class="col-6 col-lg">
            <nav class="nav flex-column">
              <h6 class="nav-header">Product</h6>
              <a href="#">Fetures</a>
              <a href="#">Pricing</a>
              <a href="#">Support</a>
              <a href="#">Demo</a>
            </nav>
          </div>

          <div class="col-6 col-lg">
            <nav class="nav flex-column">
              <h6 class="nav-header">Company</h6>
              <a href="#">About</a>
              <a href="#">Mission</a>
              <a href="#">Team</a>
              <a href="#">Contact</a>
            </nav>
          </div>

          <div class="col-6 col-lg">
            <nav class="nav flex-column">
              <h6 class="nav-header">Legal</h6>
              <a href="#">Privacy Policy</a>
              <a href="#">Terms of Service</a>
              <a href="#">API Terms</a>
              <a href="#">Insurance</a>
            </nav>
          </div>

          <div class="col-6 col-lg">
            <nav class="nav flex-column">
              <h6 class="nav-header">Other</h6>
              <a href="#">Blog</a>
              <a href="#">Forums</a>
              <a href="#">Documentation</a>
              <a href="#">Customers</a>
            </nav>
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
