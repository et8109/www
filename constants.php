<?php

final class constants {
    const maxPlayerItems = 4;
    const maxSceneItems = 4;
}
/**
 *the maximum text size in the db
 */
final class maxLength {
    const playerDesc = 1000;
    const sceneDesc = 1000;
    const itemDesc = 500;
    const keywordDesc = 255;
    const alertDesc = 100;
    const maxSpanLength = 110;
    const maxEmailLength = 35;
}

/**
 *types of alerts that can show up in the alert box
 */
final class alertTypes{
    //the number is it's id in db
    const newItem = 1;
    const hiddenItem = 2;
    const removedItem = 3;
}

/**
 *the possible actions that are visible in chat.
 *duplicated in js
 */
final class actionTypes {
    const WALKING = 0;
    const ATTACK = 1;
}

/**
 *The types of spans that you can click for a description
 */
final class spanTypes {
    const ITEM = 0;
    const PLAYER = 1;
    const SCENE = 2;
    const KEYWORD = 3;
}
/**
 *the numbers corresponding to keyword types
 */
final class keywordTypes {
    const CONTAINER = 0;
    const MATERIAL = 1;
    const QUALITY = 2;
    const SCENE_ACTION = 3;
    const APPSHP = 4;
    const MANAGER = 5;
    const LORD = 6;
    const MONARCH = 7;
}

/**
 *currently empty
 */
final class requiredSceneKeywordTypes {};
/**
 *currently empty
 */
final class requiredPlayerKeywordTypes {};

/**
 *scene keyword ID => player job keyword ID
 */
$sceneKeywordToPlayerJob = array(
    6 => 7
);

/**
 *the names asocciated with each keyword type
 */
$keywordTypeNames = array(
  0 => "container",
  1 => "material",
  2 => "quality",
  3 => "sceneAction",
  4 => "apprenticeship",
  5 => "manager",
  6 => "lord",
  7 => "monarch"
);

?>