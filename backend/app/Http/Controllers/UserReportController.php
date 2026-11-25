<?php

namespace App\Http\Controllers;

use App\Data\UserReportFilters;
use App\Data\UserReportRow;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserReportController extends Controller
{
    /**
     * @return PaginatedDataCollection<int, UserReportRow>
     */
    public function index(Request $request): PaginatedDataCollection
    {
        $filters = $this->filters($request);

        $users = $this->query($filters)->paginate($filters->perPage)->withQueryString();

        return UserReportRow::collect(
            $users->through(fn (User $user): UserReportRow => UserReportRow::fromUser($user)),
            PaginatedDataCollection::class,
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($this->filters($request));

        return response()->streamDownload(function () use ($query): void {
            $writer = SimpleExcelWriter::create('php://output', 'csv');

            foreach ($query->lazy() as $user) {
                $writer->addRow(UserReportRow::fromUser($user)->toCsvRow());
            }

            $writer->close();
        }, 'user-report-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Authorise before validating, so users without the permission get 403, not 422.
     */
    private function filters(Request $request): UserReportFilters
    {
        Gate::authorize('report', User::class);

        return UserReportFilters::validateAndCreate($request);
    }

    /**
     * @return Builder<User>
     */
    private function query(UserReportFilters $filters): Builder
    {
        return User::query()
            ->with('roles')
            ->when($filters->from, fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->to, fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()))
            ->when($filters->role, fn (Builder $query, $role) => $query->role($role->value))
            ->latest()
            ->orderByDesc('id');
    }
}
