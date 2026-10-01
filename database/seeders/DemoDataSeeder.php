<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\FeedbackVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Local development demo data for LIKHA.
 *
 * Creates realistic multi-report problem clusters so the DSS, the category
 * pages and later AI synthesis can be exercised against the real thresholds
 * (3 reports, 10 votes) using the real, unstubbed ClusteringService.
 *
 * DEVELOPMENT-ONLY ACCOUNTS
 * ------------------------
 * Twelve accounts are created with the email domain @likha-demo.test and the
 * shared password:
 *
 *     LIKHA-DEMO-ONLY-2026
 *
 * These are throwaway local fixtures. They are not real accounts, they are
 * never used in production (the seeder refuses to run there), and the password
 * is intentionally printed in this docblock so nobody has to guess it. Never
 * reuse this password anywhere real.
 *
 * SAFETY
 * ------
 * - Refuses to run outside the local and testing environments, and writes
 *   nothing when it refuses.
 * - Additive and idempotent: it only creates rows that are missing, keyed by
 *   the demo email domain, by (demo user, title) for reports, and by the
 *   natural key of each vote and comment. Running it twice changes nothing.
 * - Never deletes, truncates, or updates an existing row, and never touches
 *   Setting (thresholds), CategoryAssignment, or any non-demo record.
 * - Creates models with events suppressed, so it dispatches no job and sends
 *   no notification.
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_EMAIL_DOMAIN = '@likha-demo.test';

    private const DEMO_PASSWORD = 'LIKHA-DEMO-ONLY-2026';

    private const DEMO_USER_COUNT = 12;

    /**
     * Votes per qualifying report.
     *
     * CategoryIdeaGenerationService only considers a report once that single
     * report reaches the vote threshold on its own, so every report in a
     * qualifying cluster needs at least ten votes, not merely a cluster total.
     */
    private const VOTES_PER_QUALIFYING_REPORT = 10;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException(
                'DemoDataSeeder refused to run outside the local and testing environments. No data was written.'
            );
        }

        Model::withoutEvents(function (): void {
            $users = $this->createDemoUsers();
            $reports = $this->createDemoReports($users);
            $this->createDemoVotes($reports, $users);
            $this->createDemoComments($reports, $users);
        });

        $this->command?->info('Demo data ready. Log in as demo01@likha-demo.test with password "'.self::DEMO_PASSWORD.'".');
    }

    /**
     * @return Collection<int, User>
     */
    private function createDemoUsers(): Collection
    {
        $users = collect();

        for ($index = 1; $index <= self::DEMO_USER_COUNT; $index++) {
            $number = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $email = 'demo'.$number.self::DEMO_EMAIL_DOMAIN;

            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::query()->create([
                    'name' => 'Demo Student '.$number,
                    'email' => $email,
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'role' => 'user',
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $users->push($user);
        }

        return $users;
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, Feedback>
     */
    private function createDemoReports(Collection $users): array
    {
        $reports = [];

        foreach ($this->reportPlan() as $slug => $plan) {
            $author = $users[$plan['author']];

            $existing = Feedback::query()
                ->where('user_id', $author->id)
                ->where('title', $plan['title'])
                ->first();

            if ($existing) {
                $reports[$slug] = $existing;

                continue;
            }

            $createdAt = Carbon::now()
                ->subDays($plan['days_ago'])
                ->setTime(8 + ($plan['days_ago'] % 9), 15);

            $report = Feedback::query()->create([
                'user_id' => $author->id,
                'title' => $plan['title'],
                'description' => $plan['description'],
                'impact' => $plan['impact'],
                'category' => $plan['category'],
                'department' => $plan['department'] ?? null,
                'frequency' => $plan['frequency'],
                'current_process' => $plan['current_process'],
                'affected_users' => $plan['affected_users'],
                'affected_group' => $plan['affected_group'],
                'is_anonymous' => false,
                'is_flagged' => $plan['is_flagged'] ?? false,
                'status' => $plan['status'] ?? 'approved',
                'is_capstone_worthy' => false,
            ]);

            $report->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            $reports[$slug] = $report;
        }

        return $reports;
    }

    /**
     * The demo content plan.
     *
     * Every qualifying report deliberately repeats the signal keywords of one
     * ClusteringService profile so the real service groups it without stubs.
     * Cluster A is written in Filipino and Taglish so the later synthesis step
     * is exercised on mixed-language evidence.
     *
     * @return array<string, array<string, mixed>>
     */
    private function reportPlan(): array
    {
        return [
            // ── A. Facilities / laboratory equipment monitoring ───────────
            'a1' => [
                'author' => 0,
                'title' => 'Laboratory workstations are unavailable without asking anyone',
                'description' => 'Students walk from one laboratory to another to find a free computer, because laboratory workstation availability is never published anywhere on campus.',
                'impact' => 'Class time is lost while a computer is out of service and under maintenance without anyone noticing that it is unavailable.',
                'category' => 'Facilities',
                'department' => 'CICS',
                'frequency' => 'Everyday',
                'current_process' => 'Report verbally to staff',
                'affected_users' => '200-500',
                'affected_group' => ['Students', 'Faculty'],
                'days_ago' => 3,
            ],
            'a2' => [
                'author' => 1,
                'title' => 'Hindi malinaw kung anong laboratory ang may bakanteng computer',
                'description' => 'Pagpasok ng klase, hinahanap ng mga estudyente ang bawat laboratory para makita kung anong workstation ang available, kaya nakakalimutan nila ang computer na nakaupo na.',
                'impact' => 'Nawalan ng oras ng klase ang mga estudyente dahil walang naka-record na equipment status ng bawat laboratory.',
                'category' => 'Facilities',
                'department' => 'CICS',
                'frequency' => 'Often',
                'current_process' => 'Report verbally to staff',
                'affected_users' => '200-500',
                'affected_group' => ['Students'],
                'days_ago' => 6,
            ],
            'a3' => [
                'author' => 2,
                'title' => 'Hindi tama ang bilang ng computer sa lab reservation',
                'description' => 'May lab na nakalaan ng walang computer na nakaupo, at hindi alam ng mga estudyente kung ang occupancy ng bawat laboratory ay puno o may bakante.',
                'impact' => 'Mahabang pila sa lab entrance dahil naghahanap pa rin ng computer ang mga estudyente sa bawat laboratory wing.',
                'category' => 'Facilities',
                'department' => 'CICS',
                'frequency' => 'Sometimes',
                'current_process' => 'Manual or paper-based process',
                'affected_users' => '50-200',
                'affected_group' => ['Students', 'Faculty'],
                'days_ago' => 9,
            ],
            'a4' => [
                'author' => 3,
                'title' => 'Computer availability in teaching laboratories is unknown until arrival',
                'description' => 'Students cannot tell which laboratory has a working computer before walking over, so a workstation is claimed by whoever arrived first.',
                'impact' => 'Laboratory sessions start late and computers under maintenance are still counted as available to the class.',
                'category' => 'Facilities',
                'department' => 'CICS',
                'frequency' => 'Often',
                'current_process' => 'No solution exists at all',
                'affected_users' => '200-500',
                'affected_group' => ['Students', 'Faculty', 'Staff'],
                'days_ago' => 12,
            ],

            // ── B. Facilities / facility inspection and records ───────────
            'b1' => [
                'author' => 4,
                'title' => 'Building inspection results are recorded weeks after the visit',
                'description' => 'Each facility inspection produces a paper checklist, and the inspection result is only filed at the end of the month.',
                'impact' => 'A facility record from a previous inspection cannot be retrieved quickly, so the same fault is checked again and again.',
                'category' => 'Facilities',
                'department' => 'CFAS',
                'frequency' => 'Often',
                'current_process' => 'Manual or paper-based process',
                'affected_users' => '50-200',
                'affected_group' => ['Staff', 'Administration'],
                'days_ago' => 5,
            ],
            'b2' => [
                'author' => 5,
                'title' => 'Maintenance history is kept in a separate logbook per building',
                'description' => 'A technician writes the inspection findings in a maintenance record book, but that book stays with the staff and is never archived centrally.',
                'impact' => 'Nobody can retrieve an older inspection result, so a recurring fault is treated as a new fault every term.',
                'category' => 'Facilities',
                'department' => 'CFAS',
                'frequency' => 'Sometimes',
                'current_process' => 'Manual or paper-based process',
                'affected_users' => 'Less than 50',
                'affected_group' => ['Staff'],
                'days_ago' => 11,
            ],
            'b3' => [
                'author' => 6,
                'title' => 'Historical record of a broken room is unavailable to the next inspector',
                'description' => 'When a room is handed over, the last facility record is not filed, so the next inspection repeats the same checklist from the beginning.',
                'impact' => 'A historical record of past repairs would show how often the room fails, but that archive is not kept anywhere.',
                'category' => 'Facilities',
                'department' => 'CTE',
                'frequency' => 'Rarely',
                'current_process' => 'Just wait and hope it gets fixed',
                'affected_users' => 'Less than 50',
                'affected_group' => ['Staff', 'Faculty'],
                'days_ago' => 17,
            ],

            // ── C. Scheduling / scheduling and coordination ───────────────
            'c1' => [
                'author' => 7,
                'title' => 'Class schedule clashes between two sections in the same room',
                'description' => 'The timetable lists two sections at the same time slot, so the schedule conflict is discovered only by the instructor on the day.',
                'impact' => 'Students wait outside the room while staff settle the overlap between the two schedules.',
                'category' => 'Scheduling',
                'department' => 'Registrar',
                'frequency' => 'Often',
                'current_process' => 'Send an email or message',
                'affected_users' => '200-500',
                'affected_group' => ['Students', 'Faculty'],
                'days_ago' => 7,
            ],
            'c2' => [
                'author' => 8,
                'title' => 'Room timetable overlap between a practical and a lecture block',
                'description' => 'The schedule for one group places a lecture at the same time slot as another group practical, creating a conflict for both of them.',
                'impact' => 'Students attend the wrong class, and the schedule conflict is settled by moving whichever group is smaller.',
                'category' => 'Scheduling',
                'department' => 'Registrar',
                'frequency' => 'Sometimes',
                'current_process' => 'Report verbally to staff',
                'affected_users' => '50-200',
                'affected_group' => ['Students'],
                'days_ago' => 14,
            ],
            'c3' => [
                'author' => 9,
                'title' => 'Scheduling of shared rooms is published late each term',
                'description' => 'The schedule is posted after classes have begun, and the timetable still shows an overlap carried over from a previous term.',
                'impact' => 'Sections are moved on short notice and the timetable conflict is repeated every term because no one reviews the time slot list.',
                'category' => 'Scheduling',
                'department' => 'Registrar',
                'frequency' => 'Often',
                'current_process' => 'Broken or unreliable online system',
                'affected_users' => '200-500',
                'affected_group' => ['Students', 'Faculty', 'Administration'],
                'days_ago' => 20,
            ],

            // ── D. Enrollment / request tracking ──────────────────────────
            'd1' => [
                'author' => 10,
                'title' => 'No request status for enrollment requirements in the opening week',
                'description' => 'Students file a request about a missing requirement and then make a follow up visit, because no status update is published for it.',
                'impact' => 'Enrollment slows down and the same request receives a different answer on every follow up.',
                'category' => 'Enrollment',
                'department' => 'Registrar',
                'frequency' => 'Everyday',
                'current_process' => 'Report verbally to staff',
                'affected_users' => 'More than 500',
                'affected_group' => ['Students'],
                'days_ago' => 2,
            ],
            'd2' => [
                'author' => 11,
                'title' => 'Enrollment request status stays received for weeks',
                'description' => 'The request tracking page shows the item as received but never assigned, and no status update is posted afterwards.',
                'impact' => 'Students make a follow up trip to the office only to ask why the request has not moved.',
                'category' => 'Enrollment',
                'department' => 'Registrar',
                'frequency' => 'Often',
                'current_process' => 'Broken or unreliable online system',
                'affected_users' => '200-500',
                'affected_group' => ['Students', 'Faculty'],
                'days_ago' => 8,
            ],
            'd3' => [
                'author' => 0,
                'title' => 'Duplicate enrollment requests are opened for the same missing requirement',
                'description' => 'Because the first request is never resolved, a student files a second one, and the office assigns it to a different person.',
                'impact' => 'The request status for the duplicate stays separate, so a follow up is needed to confirm the outcome.',
                'category' => 'Enrollment',
                'department' => 'Registrar',
                'frequency' => 'Often',
                'current_process' => 'Send an email or message',
                'affected_users' => '200-500',
                'affected_group' => ['Students', 'Administration'],
                'days_ago' => 15,
            ],

            // ── E. Library / records management ───────────────────────────
            'e1' => [
                'author' => 1,
                'title' => 'Borrowed book records are not updated when an item is returned',
                'description' => 'The loan record is updated by hand at the desk, so the archive of what is still out is often a day behind.',
                'impact' => 'A student is told a document is unavailable when the previous loan record was never closed.',
                'category' => 'Library',
                'frequency' => 'Everyday',
                'current_process' => 'Manual or paper-based process',
                'affected_users' => 'More than 500',
                'affected_group' => ['Students', 'Faculty'],
                'days_ago' => 1,
            ],
            'e2' => [
                'author' => 2,
                'title' => 'No record of which shelf a pulled document came from',
                'description' => 'Staff keep a paper record of the shelf location, but the archive is not searchable and the historical record is lost during reshuffling.',
                'impact' => 'Retrieval of the same document twice in one day wastes staff time and confuses students.',
                'category' => 'Library',
                'frequency' => 'Often',
                'current_process' => 'Manual or paper-based process',
                'affected_users' => '200-500',
                'affected_group' => ['Students', 'Staff'],
                'days_ago' => 10,
            ],
            'e3' => [
                'author' => 3,
                'title' => 'Archive of past borrowing records is kept in another building',
                'description' => 'Older record sheets are stored in a separate building, so a historical record needs a formal retrieval request filed weeks ahead.',
                'impact' => 'A document reference in an old record cannot be confirmed in time for a student who needs it.',
                'category' => 'Library',
                'frequency' => 'Rarely',
                'current_process' => 'Just wait and hope it gets fixed',
                'affected_users' => '50-200',
                'affected_group' => ['Students', 'Staff', 'Faculty'],
                'days_ago' => 19,
            ],

            // ── F. Academic Process: only two reports, so it never qualifies ─
            'f1' => [
                'author' => 4,
                'title' => 'Academic process record for a course completion is never archived',
                'description' => 'The record of a completed course is kept in a spreadsheet, and the historical archive is never updated after a term ends.',
                'impact' => 'A staff member cannot retrieve a document confirming completion for a past term.',
                'category' => 'Academic Process',
                'frequency' => 'Sometimes',
                'current_process' => 'Manual or paper-based process',
                'affected_users' => '50-200',
                'affected_group' => ['Faculty', 'Administration'],
                'days_ago' => 4,
            ],
            'f2' => [
                'author' => 5,
                'title' => 'Missing record of a course outline in the academic archive',
                'description' => 'The document filed for a course outline is a scanned copy, and the historical record of revisions stays with the college.',
                'impact' => 'A request for the official document cannot be fulfilled because the record was never archived centrally.',
                'category' => 'Academic Process',
                'frequency' => 'Rarely',
                'current_process' => 'Send an email or message',
                'affected_users' => 'Less than 50',
                'affected_group' => ['Faculty'],
                'days_ago' => 18,
            ],

            // ── G. Deliberately not qualifying: pending, rejected, flagged ──
            'g1' => [
                'author' => 6,
                'title' => 'Water spilled on the floor beside a corridor',
                'description' => 'A drinking glass was knocked over in the corridor and the floor was never dried, so it stays slippery in the morning.',
                'impact' => 'Students step around the wet patch during the first class of the day.',
                'category' => 'Facilities',
                'frequency' => 'Sometimes',
                'current_process' => 'Report verbally to staff',
                'affected_users' => '50-200',
                'affected_group' => ['Students'],
                'status' => 'pending',
                'votes' => 0,
                'days_ago' => 1,
            ],
            'g2' => [
                'author' => 7,
                'title' => 'Ceiling light flickers above a stairwell landing',
                'description' => 'The light above the stairwell landing flickers for a few minutes at a time and has done so since the start of the term.',
                'impact' => 'People climbing the stairs at night cannot see the step in front of them.',
                'category' => 'Facilities',
                'frequency' => 'Often',
                'current_process' => 'Report verbally to staff',
                'affected_users' => 'Less than 50',
                'affected_group' => ['Students', 'Staff'],
                'status' => 'pending',
                'votes' => 0,
                'days_ago' => 6,
            ],
            'g3' => [
                'author' => 8,
                'title' => 'Same lift is out of order again this morning',
                'description' => 'The lift on the east side stopped working again this morning, the third time in two weeks, and nobody posted a notice.',
                'impact' => 'People carrying heavy items have to use the stairs instead.',
                'category' => 'Facilities',
                'frequency' => 'Often',
                'current_process' => 'Report verbally to staff',
                'affected_users' => '50-200',
                'affected_group' => ['Students', 'Faculty', 'Staff'],
                'status' => 'rejected',
                'votes' => 0,
                'days_ago' => 13,
            ],
            'g4' => [
                'author' => 9,
                'title' => 'Broken chair in a quiet study area',
                'description' => 'One chair in the quiet study area has a loose leg and rocks when someone sits on it, so nobody wants to use it.',
                'impact' => 'The study area has one fewer seat than the room is meant to have.',
                'category' => 'Facilities',
                'frequency' => 'Often',
                'current_process' => 'Report verbally to staff',
                'affected_users' => 'Less than 50',
                'affected_group' => ['Students'],
                'is_flagged' => true,
                'votes' => 0,
                'days_ago' => 21,
            ],
        ];
    }

    /**
     * Cast votes so every qualifying report reaches the vote threshold on its
     * own. Voters rotate from just after the author, so no demo user ever
     * votes on their own report and the (feedback_id, user_id) unique index is
     * never violated. Reports that must not qualify cast no votes.
     *
     * @param  array<string, Feedback>  $reports
     * @param  Collection<int, User>  $users
     */
    private function createDemoVotes(array $reports, Collection $users): void
    {
        $total = $users->count();

        foreach ($this->reportPlan() as $slug => $plan) {
            $report = $reports[$slug];
            $authorIndex = $plan['author'];
            $votes = $plan['votes'] ?? self::VOTES_PER_QUALIFYING_REPORT;

            for ($offset = 1; $offset <= $votes; $offset++) {
                $voter = $users[($authorIndex + $offset) % $total];

                if ($voter->id === $report->user_id) {
                    continue;
                }

                FeedbackVote::query()->firstOrCreate([
                    'feedback_id' => $report->id,
                    'user_id' => $voter->id,
                ]);
            }
        }
    }

    /**
     * A handful of comments spread across the qualifying clusters.
     *
     * @param  array<string, Feedback>  $reports
     * @param  Collection<int, User>  $users
     */
    private function createDemoComments(array $reports, Collection $users): void
    {
        $comments = [
            ['a1', 4, 'We have the same problem in the afternoon block, and the rooms are always full.'],
            ['a2', 5, 'Ganito rin sa amin, minsan tatlo pang estudyante ang pumupunta sa iisang kwarto.'],
            ['a4', 6, 'It would help a lot if the available rooms were posted before the first class.'],
            ['b1', 7, 'The checklist we use is only kept at the guard house, so nobody else can see it.'],
            ['c1', 8, 'This happened twice in the same week for our section this term.'],
            ['c3', 9, 'The schedule is posted on a board that is rarely checked, which is part of the problem.'],
            ['d1', 10, 'I filed one on the first day and was still asking about it a week later.'],
            ['e1', 11, 'The desk staff are helpful, but the loan record is often a day behind.'],
        ];

        foreach ($comments as [$slug, $userIndex, $body]) {
            $report = $reports[$slug];

            FeedbackComment::query()->firstOrCreate([
                'feedback_id' => $report->id,
                'user_id' => $users[$userIndex]->id,
                'body' => $body,
            ]);
        }
    }
}
