<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'query' => $query,
                'results' => [],
            ]);
        }

        $results = Search::new()
            ->add(
                Contact::forUser($user)
                    ->with('company'),
                ['first_name', 'last_name', 'email', 'job_title']
            )
            ->add(
                Company::forUser($user),
                ['name', 'industry', 'location']
            )
            ->add(
                Opportunity::forUser($user)
                    ->with('contact'),
                ['title', 'description']
            )
            ->beginWithWildcard()
            ->paginate(10)
            ->search($query);

        $formatted = $results->map(function ($result) {
            $model = $result->searchable;

            if ($model instanceof Contact) {
                return [
                    'type' => 'contact',
                    'id' => $model->id,
                    'title' => $model->full_name,
                    'subtitle' => $model->job_title ?? $model->email,
                    'meta' => $model->company?->name,
                    'url' => route('prospects.show', $model->id),
                ];
            }

            if ($model instanceof Company) {
                return [
                    'type' => 'company',
                    'id' => $model->id,
                    'title' => $model->name,
                    'subtitle' => $model->industry,
                    'meta' => $model->location,
                    'url' => route('companies.show', $model->id),
                ];
            }

            if ($model instanceof Opportunity) {
                return [
                    'type' => 'opportunity',
                    'id' => $model->id,
                    'title' => $model->title,
                    'subtitle' => $model->contact?->full_name,
                    'meta' => $model->stage->label(),
                    'url' => route('pipeline.index'),
                ];
            }

            return null;
        })->filter();

        return response()->json([
            'query' => $query,
            'results' => $formatted,
        ]);
    }
}
