<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧をステータスで絞り込んで表示する。
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->status;

        // ログインユーザーの読書計画を取得
        $query = ReadingPlan::with('book')
            ->where('user_id', auth()->id());

        // ステータスで読書計画を絞り込む
        if ($currentStatus) {
            $query->where('status', $currentStatus);
        }

        // 期限日順で一覧を取得
        $readingPlans = $query
            ->orderBy('target_date')
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', compact(
            'readingPlans',
            'currentStatus'
        ));
    }

    /**
     * 読書計画作成画面を表示する。
     */
    public function create(): View
    {
        // まだ読書計画を作成していない書籍を取得
        $books = Book::whereDoesntHave('readingPlans', function ($query) {
            $query->where('user_id', auth()->id());
        })->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を作成する。
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // 読書計画の初期値を設定
        $data['user_id'] = auth()->id();
        $data['status'] = ReadingPlanStatus::Reading;
        $data['completed_at'] = null;

        ReadingPlan::create($data);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書計画編集画面を表示する。
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する。
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update($request->validated());

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を削除する。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /**
     * 読書計画を読了状態に更新する。
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました。');
    }
}
