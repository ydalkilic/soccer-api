## About API Endpoints

This is a preliminary API for simulating a soccer league. Here's the provided endpoints and explanations.

- POST /api/league/create 

   Endpoint for creating random teams and builds fixture. 

- GET /api/league/
    
    Calculates and returns current standings table. Table also have championship chances if the second half in league is in progress.

- POST /api/league/simulate/{round}
    
    Simulates the matches for given round and returns the results.

- POST /api/league/simulate-all
    
    Simulates league all to the end of season and return results.

- PUT /api/league/result
    
    Updates the score of the given game with following post body:

            {
                
                "resultId": "986acbd9-81cc-4516-aedf-cfee276250ed",
                "homeGoals": 2,
                "awayGoals": 3
            }

## Config variables

Some of the league settings are located in /config/league.php . Settings are explained as follows

*  'numberOfContestants' => 4, // Number of teams of simulated league, this should be an even number and less than the count of teams array.
*  'homeAdvantageFactor' => 10, // This value is added to strength of home team to mimic supporter boost.
*  'awayDisadvantageFactor' => 5, // This value is substracted from away team to mimic opponent supporter boost.
*  'minimalStrength' => 5, // If effective strength of a team drops to 0, that means there's no mathematical chance of this team to win which is unreal. So this variable is minimum strength for a team
*  'maxGoalsForTeam' => 5, // Max number of goals a team can score on a single match.
*  'teams' => [

        'AFC Bournemouth',
        'Arsenal',
        'Aston Villa',
        'Brighton & Hove Albion',
        'Burnley',
        'Chelsea',
        'Crystal Palace',
        'Everton',
        'Leicester City',
        'Liverpool',
        'Manchester City',
        'Manchester United',
        'Newcastle United',
        'Norwich City',
        'Sheffield United',
        'Southampton',
        'Tottenham Hotspur',
        'Watford',
        'West Ham United',
        'Wolverhampton Wanderers',
  ],
