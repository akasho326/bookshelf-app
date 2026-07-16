# COACHTECH 書籍レビューアプリ BookShelf

本システムは、書籍レビューアプリケーション「BookShelf」です。
ユーザーは書籍の登録・閲覧、レビュー投稿、お気に入り登録、レビューへのいいねができます。
また、書籍検索・ジャンル絞り込み・並び替え、Google Books APIを利用したISBN検索、読書計画の作成、読書レポートの表示、通知機能などを備えています。
外部アプリケーション向けにLaravel Sanctumによる認証付き書籍管理APIも提供しています。

## 作成者

氏名 赤井翔太

## 使用技術

- PHP 8.5
- Laravel 10.x
- Laravel Fortify（認証）
- Laravel Sanctum（API認証）
- MySQL 8.4
- Nginx
- Docker / Docker Compose / Laravel Sail
- Vite / Tailwind CSS 3.4
- Google Books API
- phpMyAdmin

## ER図

```mermaid
erDiagram
    users {
        bigint_unsigned id PK
        varchar_255 name
        varchar_255 email UK
        timestamp email_verified_at
        varchar_255 password
        varchar_100 remember_token
        timestamp created_at
        timestamp updated_at
    }

    genres {
        bigint_unsigned id PK
        varchar_255 name UK
        timestamp created_at
        timestamp updated_at
    }

    books {
        bigint_unsigned id PK
        bigint_unsigned user_id FK
        varchar_255 title
        varchar_255 author
        varchar_13 isbn UK
        date published_date
        text description
        varchar_255 image_url
        timestamp created_at
        timestamp updated_at
    }

    reviews {
        bigint_unsigned id PK
        bigint_unsigned book_id FK
        bigint_unsigned user_id FK
        tinyint rating
        text comment
        timestamp created_at
        timestamp updated_at
    }

    book_genre {
        bigint_unsigned id PK
        bigint_unsigned book_id FK
        bigint_unsigned genre_id FK
        timestamp created_at
        timestamp updated_at
    }

    review_likes {
        bigint_unsigned id PK
        bigint_unsigned review_id FK
        bigint_unsigned user_id FK
        timestamp created_at
        timestamp updated_at
    }

    favorites {
        bigint_unsigned id PK
        bigint_unsigned book_id FK
        bigint_unsigned user_id FK
        timestamp created_at
        timestamp updated_at
    }

    reading_plans {
        bigint_unsigned id PK
        bigint_unsigned book_id FK
        bigint_unsigned user_id FK
        date target_date
        varchar_255 status
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    notifications {
        uuid id PK
        varchar_255 type
        varchar_255 notifiable_type
        bigint_unsigned notifiable_id
        text data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ books : "has many"
    users ||--o{ reviews : "has many"
    users ||--o{ review_likes : "has many"
    users ||--o{ favorites : "has many"
    users ||--o{ reading_plans : "has many"
    users ||--o{ notifications : "has many"
    books ||--o{ reviews : "has many" 
    books ||--o{ favorites : "has many"
    books ||--o{ book_genre : "has many"
    books ||--o{ reading_plans : "has many"
    genres ||--o{ book_genre : "has many"
    reviews ||--o{ review_likes : "has many"
```

### 制約

- reviews: UNIQUE(book_id, user_id)
- favorites: UNIQUE(book_id, user_id)
- review_likes: UNIQUE(review_id, user_id)
- book_genre: UNIQUE(book_id, genre_id)
- reading_plans: UNIQUE(book_id, user_id)

## 開発環境URL

http://localhost

## 動作環境

- Docker
- Docker Compose

※ Windowsの場合はWSL2の利用を推奨します。

## 環境構築手順

1. **リポジトリをクローン**

    ```bash
    git clone https://github.com/akasho326/bookshelf-app.git
    ```

2. **.envファイルの準備**

    `.env.example` をコピーして `.env` を作成します。

    ```bash
    cp .env.example .env
    ```

    `.env` ファイル内の以下のDB接続情報を確認・設定します。`.env.example` のデフォルト値はSail向けではないため、以下のように変更してください。

    ```ini
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=sail
    DB_PASSWORD=password
    ```

3. **Composer依存パッケージのインストール**

    プロジェクトの初回セットアップ時は、`vendor` ディレクトリが存在しないため `sail` コマンドを使用できません。
    以下のDockerコマンドを実行して、コンテナ内で `composer install` を実行します。

    ```bash
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php85-composer:latest \
        composer install --ignore-platform-reqs
    ```

4. **Laravel Sailの起動**

    以下のコマンドでDockerコンテナを起動します。

    ```bash
    ./vendor/bin/sail up -d
    ```

    > **エイリアスの設定（推奨）**
    >
    > 毎回 `./vendor/bin/sail` と入力するのは手間なので、エイリアスを設定すると便利です。
    >
    > ```bash
    > alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
    > ```

5. **アプリケーションキーの生成**

    ```bash
    sail artisan key:generate
    ```

    #### Google Books APIキーの設定

    ISBN検索機能を利用するには、Google Books APIのAPIキーが必要です。

    `.env` に以下を設定してください。

    ```env
    GOOGLE_BOOKS_API_KEY=your_api_key
    ```

    APIキーを設定していない場合、ISBN検索機能は利用できません。

6. **データベースのマイグレーションと初期データ投入**

    以下のコマンドでテーブルを作成し、ダミーデータを投入します。

    ```bash
    sail artisan migrate:fresh --seed
    ```
    このコマンドの入力後、コンテナ内にデータが残っており、エラーが生じているケースなどがあります。
    その場合は、以下のコマンドを順に実行して各コンテナを再起動して下さい。
    ```Bash
    sail down -v
    sail up -d
    sail artisan migrate:fresh --seed
    ```
    MySQLコンテナの起動には少し時間がかかる場合があります。

7. **フロントエンドのビルド**

    ```bash
    sail npm install
    sail npm run dev
    ```
    Tailwind CSSを使用しています。
    開発時は `npm run dev` でViteサーバーを起動した状態にしてください。

8. **アプリケーションへのアクセス**

    ブラウザで [http://localhost](http://localhost) にアクセスします。

## テスト実行

```bash
sail artisan test
```

カバレッジ付きで実行する場合:

```bash
sail artisan test --coverage
```

## 機能一覧

### 認証

- ユーザー登録
- ログイン
- ログアウト

### 書籍

- 書籍CRUD
- ISBN検索（Google Books API）
- キーワード検索
- ジャンル絞り込み
- 並び替え
  - 新着順
  - 古い順
  - タイトル順
  - 評価順

### ジャンル

- ジャンルCRUD

### レビュー

- CRUD
- レビューいいね

### お気に入り

- 登録・解除
- 一覧表示

### ランキング

- 平均評価ランキングTOP10

### 読書計画

- 読書計画CRUD
- 読了
- ステータス絞り込み

### 通知

- 通知一覧
- 既読
- 期限3日前通知
- 当日通知
- 期限3日後通知
- 自動期限切れ

### 読書レポート

- 評価分布
- 高評価書籍
- ジャンル別平均評価
- 読了冊数

### API

- 書籍CRUD
- キーワード検索
- ジャンル絞り込み
- ページネーション
- Sanctum認証

## APIエンドポイント一覧

GET系エンドポイントは認証不要です。

POST・PUT・DELETEはLaravel Sanctumによる認証が必要です。

全エンドポイントは `/api/v1` プレフィックス配下に定義されています。

| HTTPメソッド | URI | 概要 | 認証 |
| --- | --- | --- | --- |
| GET | /api/v1/books | 書籍一覧（キーワード検索・ジャンル絞り込み・ページネーション付き） | 不要 |
| GET | /api/v1/books/{book} | 書籍詳細（ジャンル・レビュー・評価を含む） | 不要 |
| POST | /api/v1/books | 書籍新規作成 | 必要 |
| PUT | /api/v1/books/{book} | 書籍更新 | 必要 |
| DELETE | /api/v1/books/{book} | 書籍削除 | 必要 |

## テスト

### Unitテスト

- Book
- Genre
- Favorite
- Review
- ReviewLike
- User

### Featureテスト

- 書籍CRUD
- ジャンルCRUD
- レビューCRUD
- お気に入り機能
- レビューいいね機能
- ランキング機能
- 読書計画
- 読書レポート
- 通知
- API
- 認証・認可
