<?php
namespace Cygnite\Common\SessionManager;

interface SessionInterface extends PacketInterface
{
    /**
     * Regenerates the session ID
     *
     * @return $this
     */
    public function regenerate();

    /**
     * Clears all session data and regenerates session ID
     *
     * @return $this
     */
    public function destroy();

    /**
     * Returns session identifier
     *
     * @param string $id
     * @return string
     */
    public function started($id = null);

    /**
     * Returns session name
     *
     * @param string $name
     * @return string
     */
    public function name($name = null);
}
