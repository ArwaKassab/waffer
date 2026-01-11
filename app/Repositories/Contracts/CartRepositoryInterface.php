<?php

namespace App\Repositories\Contracts;

interface CartRepositoryInterface
{
    /**
     * جلب سلة مشتريات المستخدم مع العناصر والمنتجات المرتبطة
     *
     * @param int $userId
     * @return mixed
     */
    public function getCartByUserId(int $userId);

    /**
     * إنشاء سلة جديدة لمستخدم معين
     *
     * @param int $userId
     * @return mixed
     */
    public function createCartForUser(int $userId);

    /**
     * إضافة عنصر (منتج) إلى السلة مع الكمية المطلوبة
     *
     * @param int $cartId
     * @param int $productId
     * @param int $quantity
     * @return void
     */
    public function addItem(int $cartId, int $productId, int $quantity);

    /**
     * تعديل كمية عنصر معين في السلة
     *
     * @param int $cartId
     * @param int $productId
     * @param int $quantity
     * @return void
     */
    public function updateItemQuantity(int $cartId, int $productId, int $quantity);

    /**
     * حذف عنصر (منتج) من السلة
     *
     * @param int $cartId
     * @param int $productId
     * @return void
     */
    public function removeItem(int $cartId, int $productId);

}
