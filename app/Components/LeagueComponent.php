<?php

namespace App\Components;

use Illuminate\Support\Facades\DB;
use App\Helpers\LeagueHelper;
use App\Models\Result;
use App\Models\Team;

class LeagueComponent
{
     /**
     * 
     * Creates a new league setup by creating random teams and drawing fixtures.
     * 
     * 
     * @return array teams and matches
     */
    public function createLeague() {
        try {
            Team::truncate();
            Result::truncate();
            $selectedTeamNames = LeagueHelper::selectTeamsFromPool();
            // Create teams with random strength
            foreach ($selectedTeamNames as $name) {
                $team = new Team();
                $team->name = $name;
                $team->strength = rand(1, 100);
                $team->save();
            }
            $teams = Team::all();

            $fixture = LeagueHelper::drawFixture(Team::all()->toArray());
            // Create results from fixture data
            foreach ($fixture as $match) {
                $result = new Result();
                $result->home_team_id = $match['home_team_id'];
                $result->away_team_id = $match['away_team_id'];
                $result->home_goals = null;
                $result->away_goals = null;
                $result->round = $match['round'];
                $result->save();
            }
            $matches = Result::all();
            return [
                'teams' => $teams,
                'matches' => $matches
            ];
        } catch (\Exception $e) {
            throw new \Exception('League creation failed: ' . $e->getMessage());
        }
    }

    /**
     * 
     * Creates a new league setup by creating random teams and drawing fixtures.
     * 
     * @param int $round Round number to simulate
     * 
     * @return array Results of simulated round
     */
    public function simulateRound(int $round) {
        try {
            $matchesToSimulate = Result::where('round', $round)->get();
            if ($matchesToSimulate->isEmpty()) {
                throw new \Exception('Round not found');
            }
            foreach ($matchesToSimulate as $match) {
                $matchScore = LeagueHelper::calculateGoals($match->homeTeam->strength, $match->awayTeam->strength);
                $match->home_goals = $matchScore['homeScore'];
                $match->away_goals = $matchScore['awayScore'];
                $match->save();
            }
            return Result::where('round', $round)->get();
        } catch (\Exception $e) {
            throw new \Exception('Round simulation failed: ' . $e->getMessage());
        }
    }

    /**
     * 
     * Simulates all matches in the league.
     * 
     * 
     * @return array Results of simulated matches.
     */
    public function simulateAllFixture() {
        try {
            $rounds = Result::select('round')->distinct()->get();
            foreach ($rounds as $round) {
                $this->simulateRound($round->round);
            }
            return Result::all();
        } catch (\Exception $e) {
            throw new \Exception('League simulation failed: ' . $e->getMessage());
        }
    }

    /**
     * 
     * Calculates standings table of the league.
     * 
     * 
     * @return array Results of simulated matches.
     */
    public function getLeagueTable() {
        try {
            $standingsQuery = $this->prepareLeagueTableQuery();
            $standings = DB::select($standingsQuery);
            $this->getChampionshipChances($standings);
            return $standings;
        } catch (\Exception $e) {
            throw new \Exception('League table calculation failed: ' . $e->getMessage());
        }
    }

    private function isChampionshipClinched(array $standings) {
        try {
            if (empty($standings) || $standings[0]->Games < 4) {
                return false;
            }
            $remainingRoundsCount = (config('league.numberOfContestants') - 1) * 2 - $standings[0]->Games;
            $pointDifferenceBetweenFirstAndSecond = $standings[0]->Pts - $standings[1]->Pts;
            return $remainingRoundsCount === 0 || $pointDifferenceBetweenFirstAndSecond > 3 * $remainingRoundsCount;
        } catch (\Exception $e) {
            throw new \Exception('Championship clinch check failed: ' . $e->getMessage());
        }
    }

    private function getChampionshipChances(array &$standings) {
        try {
            // If the championship is clinched, set the chance of winning the championship to 100% for the first team and 0% for the rest
            if ($this->isChampionshipClinched($standings)) {
                foreach ($standings as $key => $team) {
                    if ($key === 0) {
                        $team->chance = 100;
                    } else {
                        $team->chance = 0;
                    }
                }
            } else {
                $remainingRoundsCount = (config('league.numberOfContestants') - 1) * 2 - $standings[0]->Games;
                // Check the team chances from bottom to top to eliminate mathmetical impossibilities.
                for ($i = count($standings) -1; $i > 1 ; $i-- ) {
                    $team = $standings[$i];
                    if (!LeagueHelper::canPassPoints($team->Pts, $standings[0]->Pts, $remainingRoundsCount)) {
                        $team->chance = 0;
                    }
                }
                $totalChance = 100;
                // After elimintating the mathmetical impossibilities, calculate the chances of the remaining teams from top to bottom.
                foreach ($standings as $key => $team) {
                    if (!property_exists($team, 'chance')) {
                        $getRemainingMatches = Result::whereNull('home_goals')
                            ->whereNull('away_goals')
                            ->where(function ($query) use ($team) {
                                $query->where('home_team_id', $team->TeamId)
                                      ->orWhere('away_team_id', $team->TeamId);
                            })
                            ->get();
                        $currentChance = 1;
                        foreach ($getRemainingMatches as $match) {
                            $teamWinChance = LeagueHelper::calculateHomeWinProbability($match->homeTeam->strength, $match->awayTeam->strength);
                            if ($match->away_team_id === $team->TeamId) {
                                $teamWinChance = 100 - $teamWinChance;
                            }
                            $currentChance *= $teamWinChance / 100;
                        }
                        $effectiveChance = round($currentChance * 100);
                        $team->chance = round($effectiveChance * $totalChance / 100);
                        // Reduce total chance to calculate the effective chance of the next teams
                        $totalChance -= $team->chance;
                    }
                }

                if ($totalChance > 0) {
                    // If there is still chance left, distribute it amongst contenders to mimic unpredictable events.
                    foreach ($standings as $team) {
                        if ($team->chance > 0) {
                            $distributionAmount = round(($team->chance / (100 - $totalChance)) * $totalChance);
                            $team->chance += $distributionAmount;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Championship chances calculation failed: ' . $e->getMessage());
        }
    }

    /**
     * Prepares the query for the league table with points calculation logic.
     * 
     * TODO: This iis a raw query and should be replaced with eloquent.
     * 
     * 
     * @return string
     */
    private function prepareLeagueTableQuery() {
        $query = '
            SELECT
                x.name AS Team,
                x.id AS TeamId,
                SUM( x.played ) AS Games,
                SUM( x.win ) Won,
                SUM( x.lose ) AS Lost,
                SUM( x.tie ) AS Tied,
                SUM( x.pts ) AS Pts,
                SUM( goalsfor - goalsagainst ) AS GoalsDiff
            FROM
                (
                SELECT
                    t.name,
                    t.id,
                    1 AS played,
                IF
                    ( home_goals > away_goals, 1, 0 ) AS win,
                IF
                    ( home_goals < away_goals, 1, 0 ) AS lose,
                IF
                    ( home_goals = away_goals, 1, 0 ) AS tie,
                CASE
                        
                        WHEN home_goals > away_goals THEN
                        3 
                        WHEN home_goals < away_goals THEN
                        0 ELSE 1
                    END AS pts,
                    home_goals AS goalsfor,
                    away_goals AS goalsagainst
                FROM
                    results g INNER JOIN teams t ON g.home_team_id = t.id
                WHERE
                    g.away_goals IS NOT NULL
                    AND g.home_goals IS NOT NULL

                    UNION ALL

                SELECT
                    t.name,
                    t.id,
                    1 AS played,
                IF
                    ( home_goals < away_goals, 1, 0 ) AS win,
                IF
                    ( home_goals > away_goals, 1, 0 ) AS lose,
                IF
                    ( home_goals = away_goals, 1, 0 ) AS tie,
                CASE
                        WHEN home_goals < away_goals THEN 3 WHEN home_goals > away_goals THEN
                        0 ELSE 1
                    END AS pts,
                    away_goals AS goalsfor,
                    home_goals AS goalsagainst
                FROM
                    results g INNER JOIN teams t ON g.away_team_id = t.id
                WHERE
                    g.away_goals IS NOT NULL
                    AND g.home_goals IS NOT NULL
                ) AS x
            GROUP BY
                Team, TeamId
            ORDER BY
                Pts DESC, GoalsDiff DESC, Team ASC
            ';

            return $query;
    }
}
