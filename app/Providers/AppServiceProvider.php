<?php

namespace App\Providers;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\CoverStorage;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\ReportReader;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Persistence\PgsqlBookRepository;
use App\Infrastructure\Persistence\PgsqlCategoryRepository;
use App\Infrastructure\Persistence\PgsqlLoanRepository;
use App\Infrastructure\Persistence\PgsqlMemberRepository;
use App\Infrastructure\Persistence\PgsqlReportReader;
use App\Infrastructure\Persistence\PgsqlUserRepository;
use App\Infrastructure\Security\Argon2PasswordHasher;
use App\Infrastructure\Security\JwtTokenIssuer;
use App\Infrastructure\Storage\DiskCoverStorage;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(PasswordHasher::class, Argon2PasswordHasher::class);
        $this->app->singleton(TokenIssuer::class, fn (): JwtTokenIssuer => new JwtTokenIssuer((string) env('JWT_SECRET', 'dev-secret-change-me')));
        $this->app->singleton(CategoryRepository::class, PgsqlCategoryRepository::class);
        $this->app->singleton(BookRepository::class, PgsqlBookRepository::class);
        $this->app->singleton(MemberRepository::class, PgsqlMemberRepository::class);
        $this->app->singleton(UserRepository::class, PgsqlUserRepository::class);
        $this->app->singleton(LoanRepository::class, PgsqlLoanRepository::class);
        $this->app->singleton(ReportReader::class, PgsqlReportReader::class);
        $this->app->singleton(CoverStorage::class, fn (): DiskCoverStorage => new DiskCoverStorage((string) env('COVERS_DIR', storage_path('covers'))));
    }

    public function boot(): void
    {
        $this->bootstrapAdminFromEnvironment();
    }

    /** Usuario admin inicial desde env (D-10). Silencioso si la base aún no tiene tablas. */
    private function bootstrapAdminFromEnvironment(): void
    {
        try {
            $username = User::normalizeUsername((string) env('ADMIN_USERNAME', ''));
            $password = (string) env('ADMIN_PASSWORD', '');

            if ($username === '' || $password === '') {
                return;
            }

            $users = $this->app->make(UserRepository::class);

            if ($users->findCredentialsByUsername($username) !== null) {
                return;
            }

            $users->insert(
                User::create($username, 'admin'),
                $this->app->make(PasswordHasher::class)->hash($password),
            );
        } catch (QueryException) {
            // Motor vacío o sin migrar: el sistema debe arrancar igual (RNF-5).
        }
    }
}
