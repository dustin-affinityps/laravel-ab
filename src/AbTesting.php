<?php

namespace DustinAP\AbTesting;

use Illuminate\Support\Collection;
use DustinAP\AbTesting\Models\Goal;
use DustinAP\AbTesting\Models\Experiment;
use DustinAP\AbTesting\Events\GoalCompleted;
use DustinAP\AbTesting\Events\ExperimentNewVisitor;
use DustinAP\AbTesting\Exceptions\InvalidConfiguration;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class AbTesting {
    const SESSION_KEY_EXPERIMENT = 'ab_testing_experiment';
    const SESSION_KEY_GOALS = 'ab_testing_goals';

    protected $experiments;
    protected $enabled = false;
    protected $redirect = false;
    protected $config;

    public function __construct() {
        $this->experiments = new Collection;

        $this->setConfig();
    }

    protected function setConfig() {
        $configExperiments = config('ab-testing.experiments');
        $configGoals = config('ab-testing.goals');
        $configURLs = config('ab-testing.urls');
        $configDefault = config('ab-testing.defaultExperiment');
        $configSetOnURL = config('ab-testing.setBasedOnURL');

        if (! count($configExperiments)) {
            throw InvalidConfiguration::noExperiment();
        }

        if (count($configExperiments) !== count(array_unique($configExperiments))) {
            throw InvalidConfiguration::experiment();
        }

        if (count($configGoals) !== count(array_unique($configGoals))) {
            throw InvalidConfiguration::goal();
        }

        if (!is_array($configURLs) || count($configURLs) < 1 ) {
            $configURLs = [];
        }

        if ( (bool)config('ab-testing.enabled') ) {
            $this->enabled = true;
        }

        if ( (bool)config('ab-testing.redirect') ) {
            $this->redirect = true;
        }

        $setBasedOnURL = ($configSetOnURL || false);

        $defaultExperiment = ($configDefault ?: array_keys($configExperiments)[0]);

        $this->config = [
            'default' => $defaultExperiment,
            'setBasedOnURL' => $setBasedOnURL,
            'experiments' => $configExperiments,
            'goals' => $configGoals,
            'urls' => $configURLs,
        ];
    }

    /**
     * Validates the config items and puts them into models.
     *
     * @return void
     */
    protected function start() {
        foreach ($this->config['experiments'] as $cExperiment) {
            $experimentURL = (
                array_key_exists($cExperiment, $this->config['urls']) ?
                $this->config['urls'][$cExperiment] : env('APP_URL')
            );

            $this->experiments[] = $experiment = Experiment::firstOrCreate([
                'name' => $cExperiment,
                'url'  => $experimentURL,
            ], [
                'visitors' => 0,
            ]);

            foreach ($this->config['goals'] as $cGoal) {
                $experiment->goals()->firstOrCreate([
                    'name' => $cGoal,
                ], [
                    'hit' => 0,
                ]);
            }
        }

        session([
            self::SESSION_KEY_GOALS => new Collection,
        ]);
    }

    /**
     * Triggers a new visitor. Picks a new experiment and saves it to the session.
     *
     * @return \DustinAP\AbTesting\Models\Experiment|void
     */
    public function pageView() {
        if (config('ab-testing.ignoreCrawlers') && (new CrawlerDetect)->isCrawler()) {
            return;
        }

        if (session(self::SESSION_KEY_EXPERIMENT)) {
            return;
        }

        $this->start();
        $this->setNextExperiment();

        event(new ExperimentNewVisitor($this->getExperiment()));

        return $this->getExperiment();
    }

    /**
     * Calculates a new experiment and sets it to the session.
     *
     * @return void
     */
    protected function setNextExperiment() {
        $next = $this->getNextExperiment();

        if ( $this->redirect && $next->url != $_SERVER['SERVER_NAME'] ) {
            header('Location: https://' . $next->url);
            exit(0);
        }

        $next->incrementVisitor();

        session([
            self::SESSION_KEY_EXPERIMENT => $next,
        ]);
    }

    /**
     * Calculates a new experiment.
     *
     * @return \DustinAP\AbTesting\Models\Experiment|null
     */
    protected function getNextExperiment() {
        if ( !$this->enabled ) {
            if ( $this->config['setBasedOnURL'] ) {
                $specifiedURL = $_SERVER['SERVER_NAME'];

                $sorted = $this->experiments->where('url', '=', $specifiedURL);
            } else {
                $sorted = $this->experiments->where('name', '=', $this->config['default']);
            }
        } else {
            $sorted = $this->experiments->sortBy('visitors');
        }

        return $sorted->first();
    }

    /**
     * Checks if the currently active experiment is the given one.
     *
     * @param string $name The experiments name
     *
     * @return bool
     */
    public function isExperiment(string $name) {
        $this->pageView();

        return $this->getExperiment()->name === $name;
    }

    /**
     * Completes a goal by incrementing the hit property of the model and setting its ID in the session.
     *
     * @param string $goal The goals name
     *
     * @return \DustinAP\AbTesting\Models\Goal|false
     */
    public function completeGoal(string $goal) {
        if (! $this->getExperiment()) {
            $this->pageView();
        }

        $goal = $this->getExperiment()->goals->where('name', $goal)->first();

        if (! $goal) {
            return false;
        }

        if (session(self::SESSION_KEY_GOALS)->contains($goal->id)) {
            return false;
        }

        session(self::SESSION_KEY_GOALS)->push($goal->id);

        $goal->incrementHit();
        event(new GoalCompleted($goal));

        return $goal;
    }

    /**
     * Returns the currently active experiment.
     *
     * @return \DustinAP\AbTesting\Models\Experiment|null
     */
    public function getExperiment() {
        return session(self::SESSION_KEY_EXPERIMENT);
    }

    /**
     * Returns all the completed goals.
     *
     * @return \Illuminate\Support\Collection|false
     */
    public function getCompletedGoals() {
        if (! session(self::SESSION_KEY_GOALS)) {
            return false;
        }

        return session(self::SESSION_KEY_GOALS)->map(function ($goalId) {
            return Goal::find($goalId);
        });
    }
}
