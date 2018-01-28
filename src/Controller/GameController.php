<?php
/**
 * Created by PhpStorm.
 * User: mpant
 * Date: 1/24/2018
 * Time: 12:52 PM
 */

namespace App\Controller;

use App\Entity\Games;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{

    /**
     * @Route("/")
     */
    public function defaultPage(EntityManagerInterface $em){
        $gameList = array();
        $games = $em -> getRepository(Games::class) -> findAll();
        foreach ($games as $elem) {
            $gameList[$elem ->getId()] = $elem ->getName();
        }

        return $this->render('gamePages/defaultPage.html.twig', [
            'gameList' => $gameList,
        ]);
    }

    /**
     * @Route("/create-game-page")
     */
    public function createGamePage(Request $request, EntityManagerInterface $em){
        $gameName = $request->request->get('game-name');
        $game = new Games();
        $game->setName($gameName);
        $game->setScore(0);
        $game->setTime(new \DateTime('Asia/Kolkata'));
        $em->persist($game);
        $em->flush();

        $selectedId = $game->getId();
        return $this->render('gamePages/createGamePage.html.twig', [
            'gameId' => $selectedId,
            'gameName' => $gameName
        ]);
    }


    /**
     * @Route("/play-page")
     */
    public function playPage(Request $request, EntityManagerInterface $em) {
        $selectedID = $request->request->get('gameSelected');
        $game = $em -> getRepository(Games::class) -> find($selectedID);
        return $this->render('gamePages/playPage.html.twig', [
            'gameId' => $game->getId(),
            'gameName' => $game->getName(),
            'currentScore' => $game->getScore(),
        ]);
    }

    /**
     * @Route("/result-page/{slug}")
     */
    public function resultPage($slug, Request $request, EntityManagerInterface $em) {
        $selectedId = $request->request->get('gameSelected');
        $game = $em -> getRepository(Games::class) -> find($selectedId);
        $currentScore = $game->getScore();

        // SETTING GAME RESULTS
        $randNum = mt_rand(0,2);
        $choices = array('ROCK','PAPER', 'SCISSOR');
        $resultOptions = array('WIN', 'LOSE', 'TIE', 'NIL');
        $gameResult = $resultOptions[3];

        if ( $slug==$choices[$randNum] ){
            $gameResult = $resultOptions[2];
        }
        elseif ($slug=='ROCK' && $choices[$randNum]=='PAPER') {
            $gameResult = $resultOptions[1];
        }
        elseif ($slug=='ROCK' && $choices[$randNum]=='SCISSOR') {
            $gameResult = $resultOptions[0];
        }
        elseif ($slug=='PAPER' && $choices[$randNum]=='SCISSOR') {
            $gameResult = $resultOptions[1];
        }
        elseif ($slug=='PAPER' && $choices[$randNum]=='ROCK') {
            $gameResult = $resultOptions[0];
        }
        elseif ($slug=='SCISSOR' && $choices[$randNum]=='PAPER') {
            $gameResult = $resultOptions[0];
        }
        elseif ($slug=='SCISSOR' && $choices[$randNum]=='ROCK') {
            $gameResult = $resultOptions[1];
        }

        // SETTING GAME SCORES
        $newScore = $currentScore;
        if ($gameResult=='WIN') {
            $newScore++;
        }
        elseif ($gameResult=='LOSE') {
            $newScore--;
        }
        $game -> setScore($newScore);
        $em -> flush();

        return $this->render('gamePages/resultPage.html.twig', [
            'userPick' => $slug,
            'computerPick' => $choices[$randNum],
            'gameResult' => $gameResult,
            'newScore' => $newScore,
            'gameId' => $selectedId,
        ]);
    }

    /**
     * @Route("/watch-page")
     */
    public function watchPage(EntityManagerInterface $em) {
        $games = $em -> getRepository(Games::class) -> findAll();
        $gameList = array();
        foreach ($games as $elem) {
            //$gameList[$elem ->getName()] = $elem ->getScore();
            $playerData = [$elem->getName(), $elem->getScore(), $elem->getTime()->format('Y-m-d H:i:s')];
            array_push($gameList, $playerData);
        }
        return $this->render('gamePages/watchPage.html.twig', [
            'gameList' => $gameList,
        ]);

    }

}