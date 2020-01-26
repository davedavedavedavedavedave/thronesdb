<?php

namespace AppBundle\Helper;

use AppBundle\Entity\Card;
use AppBundle\Model\ExportableDeck;
use AppBundle\Model\SlotCollectionInterface;
use AppBundle\Model\SlotInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DeckValidationHelper
 * @package AppBundle\Helper
 */
class DeckValidationHelper
{

    /**
     * @var AgendaHelper
     */
    protected $agenda_helper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * DeckValidationHelper constructor.
     * @param AgendaHelper $agenda_helper
     * @param TranslatorInterface $translator
     */
    public function __construct(AgendaHelper $agenda_helper, TranslatorInterface $translator)
    {
        $this->agenda_helper = $agenda_helper;
        $this->translator = $translator;
    }

    /**
     * @param ExportableDeck $deck
     * @return string|null
     */
    public function findProblem(ExportableDeck $deck)
    {
        $slots = $deck->getSlots();
        $plotDeck = $slots->getPlotDeck();
        $plotDeckSize = $plotDeck->countCards();

        $expectedPlotDeckSize = 5;
        $expectedMaxDoublePlot = 0;
        $expectedMaxAgendaCount = 1;
        $expectedMinCardCount = 40;

        if ($plotDeckSize > $expectedPlotDeckSize) {
            return 'too_many_plots';
        }
        if ($plotDeckSize < $expectedPlotDeckSize) {
            return 'too_few_plots';
        }
        /* @var integer $expectedPlotDeckSpread Expected number of different plots */
        $expectedPlotDeckSpread = $expectedPlotDeckSize - $expectedMaxDoublePlot;
        if (count($plotDeck) < $expectedPlotDeckSpread) {
            return 'too_many_different_plots';
        }

        if ($slots->isAlliance()) {
            $expectedMaxAgendaCount = 3;
            $expectedMinCardCount = 75;
        }

        if ($slots->getAgendas()->countCards() > $expectedMaxAgendaCount) {
            return 'too_many_agendas';
        }
        if ($slots->getDrawDeck()->countCards() < $expectedMinCardCount) {
            return 'too_few_cards';
        }
        foreach ($slots->getCopiesAndDeckLimit() as $cardName => $value) {
            if ($value['copies'] > $value['deck_limit']) {
                return 'too_many_copies';
            }
        }
        if (!empty($this->getInvalidCards($deck))) {
            return 'invalid_cards';
        }
        foreach ($slots->getAgendas() as $slot) {
            $valid_agenda = $this->validateAgenda($slots, $slot->getCard());
            if (!$valid_agenda) {
                return 'agenda';
            }
        }

        return null;
    }

    /**
     * @param string|null $problem
     * @return string
     */
    public function getProblemLabel($problem): string
    {
        if (!$problem) {
            return '';
        }

        return $this->translator->trans('decks.problems.'.$problem);
    }

    /**
     * @param ExportableDeck $deck
     * @param Card $card
     * @return bool
     */
    public function canIncludeCard(ExportableDeck $deck, Card $card): bool
    {
        if ($card->getFaction()->getCode() !== $deck->getFaction()->getCode() && $card->getIsLoyal()) {
            return false;
        }
        return true;
    }

    /**
     * @param ExportableDeck $deck
     * @return array
     */
    protected function getInvalidCards(ExportableDeck $deck): array
    {
        $invalidCards = [];
        foreach ($deck->getSlots() as $slot) {
            if (!$this->canIncludeCard($deck, $slot->getCard())) {
                $invalidCards[] = $slot->getCard();
            }
        }

        return $invalidCards;
    }


    /**
     * @param SlotCollectionInterface $slots
     * @param Card $agenda
     * @return bool
     */
    protected function validateAgenda(SlotCollectionInterface $slots, Card $agenda): bool
    {
        return true;
    }
}
