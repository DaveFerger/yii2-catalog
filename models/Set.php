<?php

namespace robote13\catalog\models;

use Yii;
use voskobovich\linker\LinkerBehavior;
use voskobovich\linker\updaters\ManyToManySmartUpdater;

/**
 * This is the model class for table "{{%catalog_set}}".
 *
 * @property int $id
 * @property string $slug_index
 * @property string $slug
 * @property string $title
 * @property string $badge
 * @property integer $status
 * @property integer $popularity
 * @property string $description
 * @property string $discount_amount
 *
 * @property SetProduct[] $setProducts
 * @property Product[] $products
 */
class Set extends ProductBase
{
    const STATUS_ENABLED = 1;

    const STATUS_DISABLED = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%catalog_set}}';
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['relationalSave']=[
            'class' => LinkerBehavior::className(),
            'relations' => [
                'productsIds'=>[
                    'products',
                    'updater'=>[
                        'class' => ManyToManySmartUpdater::className()
                    ]
                ]
            ]
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['slug', 'title', 'description', 'discount_amount'], 'required'],
            ['productsIds','each','rule'=>['integer']],
            ['status','in','range'=> array_keys(self::getStatuses())],
            [['description'], 'string'],
            ['popularity','integer'],
            [['discount_amount'], 'number'],
            [['slug', 'title'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('robote13/catalog', 'ID'),
            'slug_index' => Yii::t('robote13/catalog', 'Slug Index'),
            'slug' => Yii::t('robote13/catalog', 'Slug'),
            'title' => Yii::t('robote13/catalog', 'Title'),
            'status' => Yii::t('robote13/catalog', 'Status'),
            'popularity' => Yii::t('robote13/catalog', 'Popularity'),
            'description' => Yii::t('robote13/catalog', 'Description'),
            'discount_amount' => Yii::t('robote13/catalog', 'Discount Amount'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSetProducts()
    {
        return $this->hasMany(SetProduct::className(), ['set_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['id' => 'product_id'])->viaTable('{{%set_product}}', ['set_id' => 'id']);
    }

    public function getPrice()
    {
        return $this->oldPrice - $this->discount_amount;
    }

    public function getOldPrice()
    {
        $price = 0;
        foreach($this->products as $product)
        {
            $price += $product->price;
        }
        return $price;
    }

    public function getIsAvailable()
    {
        foreach ($this->products as $product)
        {
            if(!$product->isAvailable)
            {
                return false;
            }
        }
        return true;
    }

    public static function getStatuses()
    {
        return [
            static::STATUS_ENABLED => \Yii::t('robote13/catalog','Enabled'),
            static::STATUS_DISABLED => \Yii::t('robote13/catalog','Disabled'),
        ];
    }

    /**
     * @inheritdoc
     * @return SetQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SetQuery(get_called_class());
    }
}
