<?php

namespace Puphpet\Controller;

use Puphpet\Controller;

use Puphpet\Domain;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session;

class Front extends Controller
{
    public function connect(Application $app)
    {
        /** @var $controllers ControllerCollection */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', [$this, 'indexAction'])
             ->bind('homepage');

        $controllers->post('/create', [$this, 'createAction'])
             ->bind('create');

        $controllers->get('/about', [$this, 'aboutAction'])
             ->bind('about');

        $controllers->get('/help', [$this, 'helpAction'])
             ->bind('help');

        return $controllers;
    }

    public function indexAction()
    {
        return $this->twig()->render(
            'Front/index.html.twig',
            ['currentPage' => 'home']
        );
    }

    public function aboutAction()
    {
        return $this->twig()->render(
            'Front/about.html.twig',
            ['currentPage' => 'about']
        );
    }

    public function helpAction()
    {
        return $this->twig()->render(
            'Front/help.html.twig',
            ['currentPage' => 'help']
        );
    }

    public function createAction(Request $request)
    {
        $box    = $request->get('box');
        $server = $request->get('server');
        $apache = $request->get('apache');
        $php    = $request->get('php');
        $mysql  = $request->get('mysql');

        $domainServer = new Domain\Server;
        $domainApache = new Domain\Apache;
        $domainMySQL  = new Domain\MySQL;

        $server['bashaliases'] = $domainServer->formatBashAliases($server['bashaliases']);
        $server['packages']    = $domainServer->formatPackages($server['packages']);

        $apache['modules'] = $domainApache->formatModules($apache['modules']);
        $apache['vhosts']  = $domainApache->formatVhosts($apache['vhosts']);

        $mysql['db'] = $domainMySQL->removeIncomplete($mysql['db']);

        $vagrantFile = $this->twig()->render('Vagrant/Vagrantfile.twig', ['box' => $box]);
        $manifest    = $this->twig()->render('Vagrant/manifest.twig', [
            'server' => $server,
            'apache' => $apache,
            'php'    => $php,
            'mysql'  => $mysql,
        ]);

        $domainFile = new Domain\File(__DIR__ . '/../repo');
        $domainFile->createArchive([
            'vagrantFile'  => $vagrantFile,
            'manifest'     => $manifest,
            'bash_aliases' => $server['bashaliases'],
        ]);
        $domainFile->downloadFile();

        return;
    }
}
