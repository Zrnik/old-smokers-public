<?php


namespace App\Presenters;


use App\Model\Endor\Character;

class MembersTemplate extends BaseTemplate
{
    /**
     * @var array<mixed>
     */
    public array $memberList;

    /**
     * @var Character[]
     */
    public array $characters = [];
}
