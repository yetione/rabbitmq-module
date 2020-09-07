<?php


namespace Yetione\RabbitMQ\Constant;


final class Exchange
{
    /**
     * Сообщения направляются в те очереди, routing_key которых, совпадает с routing_key сообщения.
     */
    const TYPE_DIRECT = 'direct';

    /**
     * Сообщения направляются в те очереди, чей шаблон routing_key соответствует routing_key сообщения.
     * Шаблоны задаются у очередей. Шаблоны и routing_key состоят из слов, разделенных точкой.
     * Слово -- набор цифробуквенных символов, ограниченый точками по краям
     * (за исключением первого и последнего символов строки), например: "agreements.eu.berlin".
     * В шаблонах можно использовать wildcard'ы:
     *  - * -- одно слово;
     *  - # -- 0 и более слов.
     * Пример:
     *  - agreements.eu.berlin.# - agreements.eu.berlin, agreements.eu.berlin.head, agreements.eu.back
     *  - agreements.eu.*.headstore - agreements.eu.berlin.headstore, agreements.eu.paris.headstore
     *
     */
    const TYPE_TOPIC = 'topic';

    /**
     * Распределение сообщениц по очередям происходит на основе заголовков. Совпадение полное или частичное.
     * Заголовки указываются как у сообщения, так и у очереди. Заголовок сообщения, который указывает в  какие
     * очереди его поместить -- x-match (any - хотя бы один заголовок совпадает, all - совпадают все заголовки).
     */
    const TYPE_HEADERS = 'headers';

    /**
     * Сообщения направляются во все привязанные очереди.
     */
    const TYPE_FANOUT = 'fanout';
}