<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Components\LeagueComponent;
use App\Http\Controllers\Controller;

class LeagueController extends Controller
{

    protected LeagueComponent $leagueComponent;

    /**
     * Create a new controller instance.
     *
     * @param  MatchComponent $matchComponent
     * @return void
     */
    public function __construct(LeagueComponent $leagueComponent)
    {
        $this->leagueComponent = $leagueComponent;
    }

    /**
     * Display current league table.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $standings = $this->leagueComponent->getLeagueTable();
            return response()->json([
                'standings' => $standings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create teams and draw fixtures.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            $createdLeague = $this->leagueComponent->createLeague();
            return response()->json([
                'teams' => $createdLeague['teams'],
                'fixture' => $createdLeague['matches']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Simulates a given round.
     *
     * @return \Illuminate\Http\Response
     */
    public function simulateRound(int $round)
    {
        try {
            return response()->json([
                'results' => $this->leagueComponent->simulateRound($round)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Simulate league all to the finish
     *
     * @return \Illuminate\Http\Response
     */
    public function simulateAll()
    {
        try {
            return response()->json([
                'results' => $this->leagueComponent->simulateAllFixture()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Simulate league all to the finish
     * 
     * TODO: We can add validation by extending Request class
     *
     * @return \Illuminate\Http\Response
     */
    public function modifyResult(Request $request)
    {
        try {
            $requestParams = $request->all();
            if (!isset($requestParams['resultId'], $requestParams['homeGoals'], $requestParams['awayGoals'])) {
                throw new \Exception('Invalid request parameters');
            }
            return response()->json([
                'results' => $this->leagueComponent->updateResult($requestParams['resultId'], $requestParams['homeGoals'], $requestParams['awayGoals'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

}
