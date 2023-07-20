<?php

namespace Gems\Dev\Clockwork\DataSource;

use Clockwork\DataSource\DataSource;
use Clockwork\Request\Request;
use Gems\Dev\Clockwork\Support\Twig\ProfilerClockworkDumper;
use Twig\Environment;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

class TwigDataSource extends DataSource
{
    // Twig environment instance
    protected $twig;

    // Twig profile instance
    protected Profile $profile;

    // Create a new data source, takes Twig environment instance as an argument
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
        $this->profile = new Profile();
    }

    // Register the Twig profiler extension
    public function listenToEvents(): void
    {
        if (class_exists(ProfilerExtension::class)) {

            $this->twig->addExtension(new ProfilerExtension($this->profile));
        }
    }

    // Adds rendered views to the request
    public function resolve(Request $request): Request
    {
        $timeline = (new ProfilerClockworkDumper())->dump($this->profile);

        $request->viewsData = array_merge($request->viewsData, $timeline->finalize());

        return $request;
    }
}