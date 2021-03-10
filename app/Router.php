<?php


namespace App;


use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

class Router
{
  /**
   * @return RouteList<Route>
   */
  public static function create(): RouteList
  {
    $router = new RouteList();

      $router->withModule("Wiki")->add(
          (new Route(
              "wiki/<presenter>/<action>",
              [
                  "presenter" => "Homepage",
                  "action" => "default"
              ]
          ))
      );


      $router->add(
          new Route(
              "<presenter>/<action>",
              [
                  "presenter" => "Homepage",
                  "action" => "default"
              ]
          )
      );


    return $router;
  }

}
