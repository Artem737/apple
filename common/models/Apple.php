<?php

namespace common\models;

use common\exceptions\AppleException;

/**
 * This is the model class for table "apple".
 *
 * @property int $id
 * @property string|null $color Цвет яблока
 * @property string|null $created_at Дата появления
 * @property string|null $fallen_at Дата падения с дерева
 * @property int|null $on_tree Упало ли яблоко. Вообще не очень понятно зачем это поле т.к. можно определить на
 * основании присутствия значения в fallen_at
 * @property float|null $remain Остаток яблока в процентах
 *
 * Для методов управления состоянием яблока можно было бы создать сервисный слой, с применением DI контейнера и т. д.
 * но в рамках тестового задания не считаю это необходимым
 *
 *
 */
class Apple extends \yii\db\ActiveRecord
{

    const LIFE_TIME = 60 * 60 * 5;

    const COLOR_RED = 'red';
    const COLOR_GREEN = 'green';
    const COLOR_YELLOW = 'yellow';

    const STATE_ON_TREE = 'Растёт';
    const STATE_FOR_EAT = 'Можно есть';
    const STATE_SPOILED = 'Испорчено';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'apple';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'fallen_at'], 'safe'],
            [['on_tree'], 'integer'],
            [['remain'], 'number'],
            [['color'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'color' => 'Color',
            'created_at' => 'Created At',
            'fallen_at' => 'Fallen At',
            'on_tree' => 'On Tree',
            'remain' => 'Remain',
        ];
    }

    /**
     * @param float $part
     * @return Apple
     * @throws AppleException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function eat(float $part) :Apple
    {

        if ($this->on_tree) {

            throw new AppleException('Нельзя съесть яблоко, которое на дереве');

        } else if ($this->isSpoiled()) {

            throw new AppleException('Нельзя съесть яблоко. Оно испортилось');
        }

        if ($part < 0 || $part > 100) {

            throw new AppleException('Нужно указать часть яблока в процентах');

        }

        $this->remain = max(0, $this->remain - $part);

        if (!$this->save()) {
            throw new AppleException('Не удалось откусить яблоко');
        }

        if ($this->remain == 0) {
            $this->delete();
        }

        return $this;
    }

    /**
     * @return Apple
     * @throws AppleException
     */
    public function fallToGround() :Apple
    {
        if ($this->on_tree) {
            $this->on_tree = 0;
            $this->fallen_at = date('Y-m-d H:i:s');
            if (!$this->save()) {
                throw new AppleException('Яблоку не удалось упасть');
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl() :string
    {
        return '/img/' . $this->color . '.png';
    }

    /**
     * @return bool
     */
    public function isSpoiled() :bool
    {
        return $this->fallen_at && time() - strtotime($this->fallen_at) > self::LIFE_TIME;
    }

    /**
     * @return string
     */
    public function state() :string
    {
        if ($this->on_tree) {

            return self::STATE_ON_TREE;

        }

        if ($this->isSpoiled()) {

            return self::STATE_SPOILED;

        }

        return self::STATE_FOR_EAT;
    }

    /**
     * @return string
     */
    public function getOpacity() :string
    {
        return str_replace(',', '.', (string)($this->remain / 100));
    }
}
