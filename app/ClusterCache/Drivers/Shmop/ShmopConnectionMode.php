<?php

namespace App\ClusterCache\Drivers\Shmop;

enum ShmopConnectionMode: string
{
    /**
     * "a" for access (sets SHM_RDONLY for shmat) use this flag when you need to
     * open an existing shared memory segment for read only
     */
    case ReadOnly = 'a';
    /**
     * "c" for create (sets IPC_CREATE) use this flag when you need to create
     * a new shared memory segment or if a segment with the same key exists,
     * try to open it for read and write
     */
    case Create = 'c';
    /**
     * "w" for read & write access use this flag when you need to read and
     * write to a shared memory segment, use this flag in most cases.
     */
    case ReadAndWite = 'w';
}