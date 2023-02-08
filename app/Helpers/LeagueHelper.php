<?php

namespace App\Helpers;

class LeagueHelper {

    /**
     * Draw a fixture for a league with the given teams.
     * 
     * @param array $teams
     *
     * @return array $matches The fixture
     */
    public static function drawFixture(array $teams){
        $numberOfTeams = count($teams);
        $numberOfRounds = ($numberOfTeams - 1) * 2;
        $numberOfMatchesPerRound = $numberOfTeams / 2;
        $matches = [];
        for ($round = 0; $round < $numberOfRounds / 2; $round++) {
            for ($match = 0; $match < $numberOfMatchesPerRound; $match++) {
                $homeTeam = $teams[$match];
                $awayTeam = $teams[$numberOfTeams - $match - 1];
                // Add the match for the first half of the season
                $matches[] = [
                    'home_team_id' => $homeTeam['id'],
                    'away_team_id' => $awayTeam['id'],
                    'round' => $round + 1,
                ];
                // Add the reverse match for the second half of the season by switching home and visitor
                $matches[] = [
                    'home_team_id' => $awayTeam['id'],
                    'away_team_id' => $homeTeam['id'],
                    'round' => $numberOfRounds - $round
                ];
            }
            $teams = array_merge(array_slice($teams, 1, $numberOfTeams - 2), array_slice($teams, 0, 1), array_slice($teams, $numberOfTeams - 1, 1));
        }
        return $matches;
    }

    /**
     * Randomly select teams from the pool of teams. (Team pool is set on league.teams in config/league.php)
     * 
     * @return array $teams Selected team names.
     */
    public static function selectTeamsFromPool() {
        $teamsPool = config('league.teams');
        $numberOfContestants = config('league.numberOfContestants');
        if ($numberOfContestants % 2 != 0) {
            throw new \Exception('Number of contestants must be even');
        }
        if ($numberOfContestants > count($teamsPool)) {
            throw new \Exception('Number of contestants must be less than or equal to the number of teams in the pool');
        }
        $selectedTeamIndices = array_rand($teamsPool, $numberOfContestants);
        return array_values(array_intersect_key($teamsPool,array_flip($selectedTeamIndices)));
    }

    /**
     * Predicts score of a game between given 2 teams with specific strengths.
     * 
     * @param int $homeTeamStrength
     * @param int $awayTeamStrength
     * 
     * @return array [homeGoals, awayGoals]
     */
    public static function calculateGoals(int $homeTeamStrength, int $awayTeamStrength) {
        $homeWinProbability = self::calculateHomeWinProbability($homeTeamStrength, $awayTeamStrength);
        $matchResult = rand(1, 100);
        $maxGoalsForTeam = config('league.maxGoalsForTeam');

        // First check if the home team wins
        if ($matchResult <= $homeWinProbability) {
            $homeGoals = rand(1, $maxGoalsForTeam);
            $awayGoals = rand(0, $homeGoals - 1);
        } else {
            // If the home team does not win, the away team wins or a there's a draw with equal chances
            $isDraw = rand(0, 1);
            if ($isDraw) {
                $homeGoals = rand(0, $maxGoalsForTeam);
                $awayGoals = $homeGoals;
            } else {
                $awayGoals = rand(1, $maxGoalsForTeam);
                $homeGoals = rand(0, $awayGoals - 1);
            }
        }

        return [
            'homeScore' => $homeGoals,
            'awayScore' => $awayGoals,
        ];
    }

    /**
     * Calculates the probability of a home win.
     * 
     * @param int $homeTeamStrength
     * @param int $awayTeamStrength
     * 
     * @return float $homeWinProbability The chance of a home win in percentage.
     */
    public static function calculateHomeWinProbability(int $homeTeamStrength, int $awayTeamStrength) {
        // Add home advantage and away disadvantage
        $effectiveHomeStrength = $homeTeamStrength + config('league.homeAdvantageFactor');
        $effectiveAwayStrength = max([$awayTeamStrength - config('league.awayDisadvantageFactor'), config('league.minimalStrength')]);

        // Calculate the probability of a home win
        $homeWinProbability = $effectiveHomeStrength / ($effectiveHomeStrength + $effectiveAwayStrength);
        $homeWinProbability = round($homeWinProbability * 100);
        return $homeWinProbability;
    }

    /**
     * Checks if a team can pass the leader in the league table mathematically.
     * 
     * @param int $teamPoints
     * @param int $leaderPoints
     * @param int $remainingRounds
     * 
     * @return bool
     */
    public static function canPassPoints(int $teamPoints, int $leaderPoints, int $remainingRounds) {
        return $leaderPoints - $teamPoints <= $remainingRounds * 3;
    }
}