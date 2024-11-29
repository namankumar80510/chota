<?php

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Chota\View\ViewInterface;

class Sample
{
    public function name(): string
    {
        return 'Nate';
    }
}

class HomeController
{
    public function __construct(
        private ViewInterface $view,
        private Sample $sample
    ) {}

    public function index(): ResponseInterface
    {
        return new HtmlResponse($this->view->render('home', ['name' => $this->sample->name()]));
    }
}
