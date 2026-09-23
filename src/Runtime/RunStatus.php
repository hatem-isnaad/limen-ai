<?php

namespace LimenAi\Runtime;

final class RunStatus
{
    public const PENDING = 'pending';

    public const RUNNING = 'running';

    public const WAITING_APPROVAL = 'waiting_approval';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';
}
