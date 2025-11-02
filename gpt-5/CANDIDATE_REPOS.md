## PHP 公開リポジトリ候補（スタイル抽出の評価用途）

以下は異なる文脈（フレームワーク/ライブラリ/大規模CMS/アプリ）ごとに、スタイル抽出の実験に適した候補です。

### 1) フレームワーク/公式デモ（ベストプラクティス型）
- Symfony Demo: `https://github.com/symfony/demo`（小規模で教科書的、PSRとSymfony流儀）
- Laravel（骨組み）: `https://github.com/laravel/laravel`（モダンPHP/ディレクトリ慣習の基準）

### 2) ライブラリ（純度の高いPSR/設計慣習）
- thephpleague/flysystem: `https://github.com/thephpleague/flysystem`
- ramsey/uuid: `https://github.com/ramsey/uuid`
- vlucas/phpdotenv: `https://github.com/vlucas/phpdotenv`

### 3) 実用Webアプリ（モダン実装の良例）
- BookStack（Laravel製Wiki）: `https://github.com/BookStackApp/BookStack`
- Flarum（フォーラム）: `https://github.com/flarum/flarum`

### 4) CMS/EC（巨大かつ独自規約の比較対象）
- WordPress Core（開発用）: `https://github.com/WordPress/wordpress-develop`
- Drupal Core: `https://github.com/drupal/core`
- Magento Open Source: `https://github.com/magento/magento2`
- PrestaShop: `https://github.com/PrestaShop/PrestaShop`
- Shopware: `https://github.com/shopware/platform`

### 5) 追加の代表FW
- Symfony Framework: `https://github.com/symfony/symfony`
- CakePHP: `https://github.com/cakephp/cakephp`

---

## 選定と比較の観点
- **規約の明瞭さ**: `phpcs.xml`/`.php-cs-fixer.php`/`phpstan.neon`/CIの有無
- **文脈の多様性**: Laravel/Symfony/CMS/EC/ライブラリなどの分布
- **サイズ/複雑性**: 小規模（デモ）→ 中規模（アプリ）→ 大規模（コア）
- **“らしさ”の強さ**: 命名/構造/依存注入/テスト流儀/設計の癖

---

## 最小セット（まずはこれでPoC）
1. symfony/demo（小規模・明快）
2. BookStackApp/BookStack（実運用レベルのLaravel）
3. WordPress/wordpress-develop（レガシ—・独自規約）
4. thephpleague/flysystem（ライブラリ純度）
5. magento/magento2（巨大・拡張モデル）


