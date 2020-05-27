<?php

use yii\db\Migration;

/**
 * Создание таблицы с яблоками
 */
class m200527_032818_create_apple_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //Индексов нет т.к условием задачи не предусмотрен какой-либо поиск, а вот изменение данных будет постоянно
        $this->createTable('{{%apple}}', [
            'id' => $this->primaryKey(),
            'color' => $this->string(100)->comment('Цвет яблока'),
            'created_at' => $this->dateTime()->comment('Дата появления'),
            'fallen_at' => $this->dateTime()->comment('Дата падения с дерева')->null(),
            'on_tree' => $this->boolean()->comment('Упало ли яблоко. Вообще не очень понятно зачем это поле т.к. можно ' .
                'определить на основании присутствия значения в fallen_at')->defaultValue(1),
            'remain' => $this->float()->comment('Остаток яблока в процентах')->defaultValue(100)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%apple}}');
    }
}
