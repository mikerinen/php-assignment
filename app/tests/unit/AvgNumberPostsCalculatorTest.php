<?php

declare(strict_types = 1);

namespace Tests\unit;

use PHPUnit\Framework\TestCase;

use DateTime;
use Statistics\Calculator\AvgNumberPostsCalculator;
use Statistics\Calculator\Factory\StatisticsCalculatorFactory;
use Statistics\Dto\ParamsTo;
use Statistics\Enum\StatsEnum;
use SocialPost\Dto\SocialPostTo;
use SocialPost\Hydrator\FictionalPostHydrator;

/**
 * Class AvgNumberPostsCalculatorTestSuite
 *
 * @package Tests\unit
 */
class AvgNumberPostsCalculatorTestSuite extends TestCase
{
    /**
     * Checks that the average per user per month is calculated correctly with a wide startDate and endDate
     *
     * @test
     */
    public function shouldReturnCorrectStatsSuccessfully(): void
    {
        // setup
        $params = new ParamsTo();
        $params->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH);
        $params->setStartDate(new DateTime('2023-01-01 00:00:00')); // four month period to avoid skipping some posts
        $params->setEndDate(new DateTime('2023-04-30 23:59:59'));

        $factory = new StatisticsCalculatorFactory();
        $calculator = $factory->create([$params]);
        $hydrator = new FictionalPostHydrator();

        $testPosts = [
          $hydrator->hydrate(array("id"=>"1", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-04-11T11:00:00+03:00")),
          $hydrator->hydrate(array("id"=>"2", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-03-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"3", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-02-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"4", "from_name"=>"bla", "from_id"=>"user_2", "message"=>"m", "type"=>"status", "created_time"=>"2023-03-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"5", "from_name"=>"bla", "from_id"=>"user_2", "message"=>"m", "type"=>"status", "created_time"=>"2023-02-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"6", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-01-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"7", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-01-11T11:00:00+02:00"))
        ];

        // execute
        foreach ($testPosts as $post) {
            if (!$post instanceof SocialPostTo) {
                continue;
            }
            $calculator->accumulateData($post);
        }
        $stats = $calculator->calculate();

        // validate
        $this->assertTrue($stats->getChildren()[0]->getChildren()[0]->getName() == 'average-posts-per-user');
        $this->assertTrue($stats->getChildren()[0]->getChildren()[0]->getValue() == 1.25);
        $this->assertTrue($stats->getChildren()[0]->getChildren()[1]->getValue() == 1); // note the user_2 made only two posts over two months
    }

    /**
     * Checks that the startDate and endDate does not intefere with the calculation
     *
     * @test
     */
    public function shouldReturnCorrectStatsSuccessfullyDespiteMoreThanAYearPeriod(): void
    {
        // setup
        $params = new ParamsTo();
        $params->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH);
        $params->setStartDate(new DateTime('2022-01-01 00:00:00')); // more than a year period
        $params->setEndDate(new DateTime('2023-04-30 23:59:59'));

        $factory = new StatisticsCalculatorFactory();
        $calculator = $factory->create([$params]);
        $hydrator = new FictionalPostHydrator();

        $testPosts = [
          $hydrator->hydrate(array("id"=>"1", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-04-11T11:00:00+03:00")),
          $hydrator->hydrate(array("id"=>"2", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-03-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"3", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-02-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"4", "from_name"=>"bla", "from_id"=>"user_2", "message"=>"m", "type"=>"status", "created_time"=>"2023-03-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"5", "from_name"=>"bla", "from_id"=>"user_2", "message"=>"m", "type"=>"status", "created_time"=>"2023-02-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"6", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-01-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"7", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-01-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"8", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2022-01-11T11:00:00+02:00"))
        ];

        // execute
        foreach ($testPosts as $post) {
            if (!$post instanceof SocialPostTo) {
                continue;
            }
            $calculator->accumulateData($post);
        }
        $stats = $calculator->calculate();

        // validate
        $this->assertTrue($stats->getChildren()[0]->getChildren()[0]->getName() == 'average-posts-per-user');
        $this->assertTrue($stats->getChildren()[0]->getChildren()[0]->getValue() == 0.38); // 16 months worth of posts
        $this->assertTrue($stats->getChildren()[0]->getChildren()[1]->getValue() == 1); // only two months worth of posts
    }

    /**
     * Checks that the the startDate and endDate limit the posts that are evaluated
     *
     * @test
     */
    public function shouldIgnoreOutOfBoundsPostsAndReturnCorrectStatsSuccessfullyForLessThanMonth(): void
    {
        // setup
        $params = new ParamsTo();
        $params->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH);
        $params->setStartDate(new DateTime('2023-04-01 00:00:00')); // note less than a month
        $params->setEndDate(new DateTime('2023-04-30 23:59:59'));

        $factory = new StatisticsCalculatorFactory();
        $calculator = $factory->create([$params]);
        $hydrator = new FictionalPostHydrator();

        $testPosts = [
          $hydrator->hydrate(array("id"=>"1", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-04-11T11:00:00+03:00")),
          $hydrator->hydrate(array("id"=>"2", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-03-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"3", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-02-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"4", "from_name"=>"bla", "from_id"=>"user_2", "message"=>"m", "type"=>"status", "created_time"=>"2023-03-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"5", "from_name"=>"bla", "from_id"=>"user_2", "message"=>"m", "type"=>"status", "created_time"=>"2023-02-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"6", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-01-11T11:00:00+02:00")),
          $hydrator->hydrate(array("id"=>"7", "from_name"=>"bla", "from_id"=>"user_1", "message"=>"m", "type"=>"status", "created_time"=>"2023-01-11T11:00:00+02:00"))
        ];

        // execute
        foreach ($testPosts as $post) {
            if (!$post instanceof SocialPostTo) {
                continue;
            }
            $calculator->accumulateData($post);
        }
        $stats = $calculator->calculate();

        // validate
        $this->assertTrue($stats->getChildren()[0]->getChildren()[0]->getName() == 'average-posts-per-user');
        $this->assertTrue(count($stats->getChildren()[0]->getChildren()) == 1); // there should be only user_1 stats
        $this->assertTrue($stats->getChildren()[0]->getChildren()[0]->getValue() == 1); // there should be only one post
    }
}
