<?php

/**
 *types of alerts that can show up in the alert box
 */
final class alertTypes{
    //the number is it's id in db
    const newItem = 1;
    const hiddenItem = 2;
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
    const PLAYER_JOB = 4;
}

/**
 *the keyword types required in all items
 *1: material
 *2:quality
 */
final class requiredItemKeywordTypes {
    const material = 1;
    const quality = 2;
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
 * keyword ID => increase in combat skill
 */
$combatItemKeywords = array(
    2 => 2,
    4 => 1
);

/**
 *scene keyword ID => player job keyword ID
 */
$sceneKeywordToPlayerJob = array(
    6 => 7
);
?>